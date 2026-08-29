# Extending Flows: Custom Operations and Triggers

Third-party packages can add flow operations and triggers to Filament Studio by registering them from a service provider. The registration API is stable and follows semantic versioning — see [Semver policy](#semver-policy) below.

## Contents

- [Quickstart](#quickstart)
- [Operations](#operations)
  - [FlowOperation](#flowoperation)
  - [FlowOperationConfig](#flowoperationconfig)
  - [OperationContext](#operationcontext)
  - [DataChain](#datachain)
  - [OperationResult](#operationresult)
- [Triggers](#triggers)
  - [FlowTrigger](#flowtrigger)
  - [FlowTriggerConfig](#flowtriggerconfig)
- [Registration](#registration)
- [Config field-type catalog](#config-field-type-catalog)
- [Semver policy](#semver-policy)
- [Complete example: Slack operation package](#complete-example-slack-operation-package)

---

## Quickstart

This 30-line example registers a custom operation that pings a URL and returns the HTTP status code.

```php
<?php

// src/PingActivity.php
namespace Acme\StudioPing;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Illuminate\Support\Facades\Http;

class PingActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $url = $context->interpolate((string) ($context->config()['url'] ?? ''));

        try {
            $status = Http::timeout(5)->get($url)->status();
        } catch (\Throwable $e) {
            return OperationResult::fail('Ping failed: ' . $e->getMessage(), $e);
        }

        return OperationResult::success(['status' => $status]);
    }
}
```

```php
<?php

// src/PingServiceProvider.php
namespace Acme\StudioPing;

use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Illuminate\Support\ServiceProvider;

class PingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentStudio::registerFlowOperation(
            key: 'acme_ping',
            label: 'Ping URL',
            activity: PingActivity::class,
            icon: 'heroicon-o-signal',
            group: 'developer',
        );
    }
}
```

Publish, install, and the `Ping URL` operation appears in the canvas palette under **Developer**.

---

## Operations

### FlowOperation

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\FlowOperation`

```php
interface FlowOperation
{
    public function execute(OperationContext $context): OperationResult;
}
```

Implement this interface in your activity class. The engine resolves it via the Laravel service container, so constructor dependencies are autowired.

### FlowOperationConfig

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig`

```php
interface FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /**
     * @param  array<string, mixed>  $config
     * @throws \Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException
     */
    public function validate(array $config): void;
}
```

`schema()` returns a descriptor array that drives the canvas config panel. The shape is:

```php
[
    'fields' => [
        ['name' => 'url',     'type' => 'text',    'label' => 'URL'],
        ['name' => 'method',  'type' => 'select',  'label' => 'Method',
         'options' => [['value' => 'GET', 'label' => 'GET'], ['value' => 'POST', 'label' => 'POST']]],
        ['name' => 'headers', 'type' => 'keyvalue', 'label' => 'Headers'],
    ],
]
```

`defaults()` returns a flat `['field_name' => defaultValue]` map merged into the config before `validate()` is called.

`validate()` should throw `InvalidOperationConfigException` when the config is invalid. The exception accepts a field-keyed `$errors` array surfaced to the user in the canvas:

```php
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

public function validate(array $config): void
{
    if (empty($config['url'])) {
        throw new InvalidOperationConfigException('URL is required', ['url' => 'required']);
    }
}
```

The config schema is **validated at publish time** — a flow cannot be published if any operation's stored config fails `validate()`.

### OperationContext

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\OperationContext`

The context object is constructed by the engine and passed to `execute()`. It is `final readonly`.

| Method | Return | Description |
|--------|--------|-------------|
| `config()` | `array<string, mixed>` | The operation's stored config with defaults merged in. |
| `dataChain()` | `DataChain` | Read-only bag of trigger data and preceding operation outputs. |
| `flow()` | `StudioFlow` | The flow model currently executing. |
| `run()` | `StudioFlowRun` | The run model tracking this execution. |
| `tenantId()` | `string` | The current tenant's identifier. |
| `interpolate(string $template)` | `string` | Resolves `{{ $trigger.field }}` and `{{ op_key.field }}` tokens against the data chain. |

#### Template interpolation

Use `$context->interpolate()` to resolve user-typed reference expressions from config values before sending them to external services:

```php
$url     = $context->interpolate($context->config()['url']);     // e.g. https://api.example.com/{{ $trigger.id }}
$subject = $context->interpolate($context->config()['subject']); // e.g. Hello {{ $trigger.name }}
```

Token syntax: `{{ $trigger.fieldName }}`, `{{ $trigger.nested.path }}`, `{{ op_key.output_field }}`, `{{ $last.field }}`.

Unknown paths resolve to an empty string. Arrays are JSON-encoded inline.

### DataChain

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\DataChain`

The data chain is a read-only snapshot of every output produced so far in the current run. It is `final readonly`.

| Method | Return | Description |
|--------|--------|-------------|
| `trigger()` | `array<string, mixed>` | The payload that fired the trigger (webhook body, schedule metadata, etc.). |
| `last()` | `mixed` | The `output` array from the immediately preceding operation, or `null` if this is the first op. |
| `get(string $opKey)` | `mixed` | The output from a named earlier operation. Returns `null` if the key is not found. |
| `toArray()` | `array<string, mixed>` | Flat bag of all chain data. Keys include `$trigger`, `$last`, each op key with and without a `$` prefix. |

Typically you access the chain through `$context->interpolate()` rather than calling these methods directly. Use them when you need structured data rather than a rendered string.

### OperationResult

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\OperationResult`

`OperationResult` is a `final readonly` value object returned from `execute()`. Use the three static factory methods:

```php
// Success — passes output to the next operation in the chain
OperationResult::success(array $output = []): self

// Failure — halts the run and records the error
OperationResult::fail(string $message, ?\Throwable $previous = null): self

// Branched success — routes execution to a named edge (e.g. 'success' / 'failure')
OperationResult::withBranch(string $branch, array $output = []): self
```

Branching is used by condition-style operations where the canvas has multiple outgoing edges. The `$branch` string must match one of the edge labels drawn in the canvas for routing to work.

```php
// Example: route on HTTP response status
if ($response->successful()) {
    return OperationResult::withBranch('success', ['status' => $response->status()]);
}
return OperationResult::withBranch('failure', ['status' => $response->status()]);
```

Inspector methods: `isSuccess()`, `isFailure()`, `output()`, `branch()`, `message()`, `previous()`.

---

## Triggers

A trigger is a lifecycle hook that activates when a flow version is published or unpublished. Its job is to wire external infrastructure (cron jobs, webhook URLs, event subscriptions) to the flow engine.

### FlowTrigger

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger`

```php
interface FlowTrigger
{
    public function register(StudioFlowVersion $version): void;

    public function unregister(StudioFlowVersion $version): void;
}
```

`register()` is called when a flow version is published. `unregister()` is called when a flow version is retired or deleted. Both receive the `StudioFlowVersion` model, which provides the full graph definition via `$version->graph`.

The trigger config stored in the graph is available via:

```php
$node   = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');
$config = $node['data']['config'] ?? [];
```

For triggers that only respond to inbound events at a fixed route (e.g., webhooks already handled by the package's webhook controller), `register()` and `unregister()` can be empty.

### FlowTriggerConfig

**Namespace:** `Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig`

```php
interface FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /** @param array<string, mixed> $config */
    public function validate(array $config): void;
}
```

The shape of `schema()` and `defaults()` follows the same format as `FlowOperationConfig`.

---

## Registration

Call `FilamentStudio::registerFlowOperation()` and `FilamentStudio::registerFlowTrigger()` from your service provider's `boot()` method.

```php
use Flexpik\FilamentStudio\Facades\FilamentStudio;

public function boot(): void
{
    FilamentStudio::registerFlowOperation(
        key: 'acme_send_slack',      // unique, snake_case, namespaced by your vendor prefix
        label: 'Send Slack Message', // shown in the canvas palette
        activity: SendSlackActivity::class,
        configSchema: SendSlackConfig::class, // optional — omit if no config
        icon: 'heroicon-o-chat-bubble-left',  // optional — Heroicons v2 outline name
        group: 'communication',               // optional — palette section (default: 'general')
        dangerous: false,                     // optional — gates on run_dangerous_operations permission
    );

    FilamentStudio::registerFlowTrigger(
        key: 'acme_stripe_webhook',
        label: 'Stripe Webhook',
        handler: StripeWebhookTrigger::class,
        configSchema: StripeWebhookConfig::class, // optional
        icon: 'heroicon-o-credit-card',           // optional
    );
}
```

**Operation groups** control the palette section header. Built-in groups: `logic`, `data`, `communication`, `records`, `developer`, `workflow`. Any other string creates a new section.

**Duplicate keys** throw `DuplicateFlowOperationException` (or `DuplicateFlowTriggerException`) at registration time. Namespace your keys with a vendor prefix (`acme_`, `mypackage_`, etc.) to avoid collisions.

**Dangerous operations** (`dangerous: true`) require the operator to hold the `run_dangerous_operations` permission before the flow can be published. Use this for operations that execute arbitrary code, make irreversible changes, or access sensitive infrastructure.

---

## Config field-type catalog

The canvas config panel supports nine field types. This catalog is **frozen** — new types are minor-version additions; removals or changes are major-version breaks.

| Type | `"type"` value | Description | Example usage |
|------|----------------|-------------|---------------|
| Text | `"text"` | Single-line string input. Supports `placeholder` and `required`. | URL, API key, recipient address |
| Textarea | `"textarea"` | Multi-line text. Supports `rows`. | Message body, description |
| Number | `"number"` | Numeric input. Supports `min`, `max`, `step`. | Timeout (seconds), retry count |
| Boolean | `"boolean"` | Toggle switch. Default is `false`. | Fail on error, include metadata |
| Select | `"select"` | Dropdown. Requires a static `options` array or a `$source` reference. | HTTP method, locale, environment |
| Key-value | `"keyvalue"` | Map editor for arbitrary string-to-string pairs. | HTTP headers, query parameters |
| Code | `"code"` | Monospace editor. Supports `language` (`json`, `javascript`, `text`). | Request body (JSON), template |
| Flow select | `"flow_select"` | Picks another flow by ID from the tenant's flows. | Trigger sub-flow |
| Collection select | `"collection_select"` | Picks a Studio collection by ID. | Target collection for record ops |

### Schema examples

**Text field with placeholder:**
```php
['name' => 'webhook_url', 'type' => 'text', 'label' => 'Webhook URL',
 'placeholder' => 'https://hooks.slack.com/services/...', 'required' => true]
```

**Select with static options:**
```php
['name' => 'method', 'type' => 'select', 'label' => 'HTTP Method',
 'options' => [
     ['value' => 'GET',  'label' => 'GET'],
     ['value' => 'POST', 'label' => 'POST'],
 ]]
```

**Boolean toggle:**
```php
['name' => 'fail_on_error', 'type' => 'boolean', 'label' => 'Fail on error']
```

**Key-value map:**
```php
['name' => 'headers', 'type' => 'keyvalue', 'label' => 'Custom Headers']
```

**Code editor (JSON):**
```php
['name' => 'body', 'type' => 'code', 'label' => 'Request Body', 'language' => 'json']
```

**Number with bounds:**
```php
['name' => 'timeout', 'type' => 'number', 'label' => 'Timeout (seconds)', 'min' => 1, 'max' => 120]
```

**Flow select:**
```php
['name' => 'sub_flow_id', 'type' => 'flow_select', 'label' => 'Flow to trigger']
```

**Collection select:**
```php
['name' => 'collection_id', 'type' => 'collection_select', 'label' => 'Target Collection']
```

---

## Semver policy

| Namespace | Stability | Change policy |
|-----------|-----------|---------------|
| `Flexpik\FilamentStudio\Contracts\Flows\*` | **Public** | Breaking changes only in major releases. |
| `Flexpik\FilamentStudio\Facades\FilamentStudio` | **Public** | New methods are minor; signature changes are major. |
| `Flexpik\FilamentStudio\Flows\Exceptions\*` | **Public** | Exception class names and public methods are stable. |
| `Flexpik\FilamentStudio\Flows\*` (all others) | **Internal** | May change in any release without notice. |

If you accidentally depend on an internal class, the CHANGELOG will include a "deprecated" notice and a one-major-release grace period before removal.

**What counts as a breaking change in operations:**

- Removing or renaming a method on a public contract interface.
- Changing a method's parameter list or return type.
- Removing a config field type from the catalog.
- Renaming or removing a built-in operation or trigger key that third parties chain to.

**What does not count as a breaking change:**

- Adding a new optional method to a contract (only if you also ship a default via a trait or abstract class — otherwise it is technically breaking for implementors; we will never do this).
- Adding new config field types.
- Adding new built-in operations or triggers.
- Changing internal routing, run storage, or canvas rendering logic.

---

## Complete example: Slack operation package

This section walks through building `acme/filament-studio-slack` as a standalone Composer package.

### Directory structure

```
acme/filament-studio-slack/
├── composer.json
├── src/
│   ├── SlackServiceProvider.php
│   ├── Operations/
│   │   ├── SendSlackMessageActivity.php
│   │   └── SendSlackMessageConfig.php
│   └── Triggers/
│       ├── SlackEventTrigger.php
│       └── SlackEventTriggerConfig.php
└── tests/
    └── SendSlackMessageTest.php
```

### composer.json

```json
{
    "name": "acme/filament-studio-slack",
    "description": "Slack operations and triggers for Filament Studio flows",
    "require": {
        "php": "^8.2",
        "flexpik/filament-studio": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Acme\\FilamentStudioSlack\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Acme\\FilamentStudioSlack\\SlackServiceProvider"
            ]
        }
    }
}
```

### Service provider

```php
<?php

declare(strict_types=1);

namespace Acme\FilamentStudioSlack;

use Acme\FilamentStudioSlack\Operations\SendSlackMessageActivity;
use Acme\FilamentStudioSlack\Operations\SendSlackMessageConfig;
use Acme\FilamentStudioSlack\Triggers\SlackEventTrigger;
use Acme\FilamentStudioSlack\Triggers\SlackEventTriggerConfig;
use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Illuminate\Support\ServiceProvider;

class SlackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentStudio::registerFlowOperation(
            key: 'acme_send_slack_message',
            label: 'Send Slack Message',
            activity: SendSlackMessageActivity::class,
            configSchema: SendSlackMessageConfig::class,
            icon: 'heroicon-o-chat-bubble-left-right',
            group: 'communication',
        );

        FilamentStudio::registerFlowTrigger(
            key: 'acme_slack_event',
            label: 'Slack Event',
            handler: SlackEventTrigger::class,
            configSchema: SlackEventTriggerConfig::class,
            icon: 'heroicon-o-bolt',
        );
    }
}
```

### Operation activity

```php
<?php

declare(strict_types=1);

namespace Acme\FilamentStudioSlack\Operations;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Illuminate\Support\Facades\Http;

class SendSlackMessageActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config     = $context->config();
        $webhookUrl = (string) ($config['webhook_url'] ?? '');
        $text       = $context->interpolate((string) ($config['text'] ?? ''));
        $username   = (string) ($config['username'] ?? 'Studio Bot');
        $iconEmoji  = (string) ($config['icon_emoji'] ?? ':robot_face:');

        if ($webhookUrl === '') {
            return OperationResult::fail('Slack webhook URL is not configured.');
        }

        try {
            $response = Http::timeout(10)->post($webhookUrl, [
                'text'       => $text,
                'username'   => $username,
                'icon_emoji' => $iconEmoji,
            ]);
        } catch (\Throwable $e) {
            return OperationResult::fail('Slack request failed: ' . $e->getMessage(), $e);
        }

        if ($response->failed()) {
            return OperationResult::fail(
                'Slack returned HTTP ' . $response->status() . ': ' . $response->body(),
            );
        }

        return OperationResult::success([
            'slack_status' => $response->status(),
        ]);
    }
}
```

### Operation config schema

```php
<?php

declare(strict_types=1);

namespace Acme\FilamentStudioSlack\Operations;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class SendSlackMessageConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                [
                    'name'        => 'webhook_url',
                    'type'        => 'text',
                    'label'       => 'Slack Webhook URL',
                    'placeholder' => 'https://hooks.slack.com/services/...',
                    'required'    => true,
                ],
                [
                    'name'  => 'text',
                    'type'  => 'textarea',
                    'label' => 'Message Text',
                    'rows'  => 3,
                ],
                [
                    'name'  => 'username',
                    'type'  => 'text',
                    'label' => 'Bot Username',
                ],
                [
                    'name'  => 'icon_emoji',
                    'type'  => 'text',
                    'label' => 'Icon Emoji',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'username'   => 'Studio Bot',
            'icon_emoji' => ':robot_face:',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        if (empty($config['webhook_url'])) {
            throw new InvalidOperationConfigException(
                'webhook_url is required',
                ['webhook_url' => 'The Slack webhook URL is required.'],
            );
        }

        if (! str_starts_with((string) $config['webhook_url'], 'https://hooks.slack.com/')) {
            throw new InvalidOperationConfigException(
                'webhook_url must be a Slack webhook URL',
                ['webhook_url' => 'Must start with https://hooks.slack.com/'],
            );
        }
    }
}
```

### Trigger handler

```php
<?php

declare(strict_types=1);

namespace Acme\FilamentStudioSlack\Triggers;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

class SlackEventTrigger implements FlowTrigger
{
    public function register(StudioFlowVersion $version): void
    {
        // Slack Events API subscriptions are managed via Slack's API.
        // Here you would call Slack's API to subscribe the flow's webhook endpoint
        // to the event types specified in the trigger config.
        $node   = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');
        $config = $node['data']['config'] ?? [];
        $events = (array) ($config['event_types'] ?? ['message']);

        // e.g. SlackApiClient::subscribeEvents($version->flow->id, $events);
        // (implementation depends on your Slack app setup)
    }

    public function unregister(StudioFlowVersion $version): void
    {
        // Unsubscribe the flow's webhook from Slack Events API.
        // e.g. SlackApiClient::unsubscribeEvents($version->flow->id);
    }
}
```

### Trigger config schema

```php
<?php

declare(strict_types=1);

namespace Acme\FilamentStudioSlack\Triggers;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class SlackEventTriggerConfig implements FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                [
                    'name'    => 'event_types',
                    'type'    => 'select',
                    'label'   => 'Event Types',
                    'options' => [
                        ['value' => 'message',         'label' => 'Message posted'],
                        ['value' => 'reaction_added',  'label' => 'Reaction added'],
                        ['value' => 'channel_created', 'label' => 'Channel created'],
                    ],
                ],
                [
                    'name'  => 'channel_filter',
                    'type'  => 'text',
                    'label' => 'Channel Filter (optional)',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['event_types' => 'message'];
    }

    /** @param array<string, mixed> $config */
    public function validate(array $config): void
    {
        // All fields are optional — accept any config.
    }
}
```

### Testing your operation

```php
<?php

use Acme\FilamentStudioSlack\Operations\SendSlackMessageActivity;
use Flexpik\FilamentStudio\Contracts\Flows\DataChain;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Support\Facades\Http;

it('sends a Slack message and returns the status code', function (): void {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

    $chain   = new DataChain(trigger: ['name' => 'Alice'], outputs: []);
    $context = new OperationContext(
        flow: StudioFlow::factory()->make(),
        run: StudioFlowRun::factory()->make(),
        dataChain: $chain,
        config: [
            'webhook_url' => 'https://hooks.slack.com/services/T000/B000/xxxx',
            'text'        => 'Hello {{ $trigger.name }}',
            'username'    => 'Test Bot',
            'icon_emoji'  => ':white_check_mark:',
        ],
        tenantId: 'tenant-1',
    );

    $result = (new SendSlackMessageActivity)->execute($context);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->output()['slack_status'])->toBe(200);

    Http::assertSent(fn ($req) => str_contains($req->body(), 'Hello Alice'));
});
```

### Installation

```bash
composer require acme/filament-studio-slack
```

The service provider is auto-discovered. No additional configuration is required beyond adding your Slack webhook URL to the operation's canvas config panel.

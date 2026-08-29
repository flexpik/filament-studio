<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowContext;
use Flexpik\FilamentStudio\Flows\Engine\Templating\TemplateEngine;

beforeEach(function () {
    $this->engine = new TemplateEngine;
    $this->ctx = FlowContext::make(
        trigger: ['email' => 'a@b.com', 'nested' => ['x' => 1]],
    );
    $this->ctx->set('create_user', ['id' => 42]);
});

it('resolves a simple dot path', function () {
    expect($this->engine->renderString('{{ $trigger.email }}', $this->ctx))
        ->toBe('a@b.com');
});

it('resolves nested paths', function () {
    expect($this->engine->renderString('{{ $trigger.nested.x }}', $this->ctx))->toBe('1');
});

it('resolves operation outputs by key', function () {
    expect($this->engine->renderString('user-{{ create_user.id }}', $this->ctx))->toBe('user-42');
});

it('returns empty string for missing paths', function () {
    expect($this->engine->renderString('[{{ $trigger.missing }}]', $this->ctx))->toBe('[]');
});

it('renderArray walks all leaves recursively', function () {
    $config = [
        'to' => '{{ $trigger.email }}',
        'meta' => ['user_id' => '{{ create_user.id }}', 'static' => 'no-template'],
        'list' => ['{{ $trigger.email }}', 'plain'],
    ];

    expect($this->engine->renderArray($config, $this->ctx))->toBe([
        'to' => 'a@b.com',
        'meta' => ['user_id' => '42', 'static' => 'no-template'],
        'list' => ['a@b.com', 'plain'],
    ]);
});

it('does not execute PHP — function calls are returned as text', function () {
    expect($this->engine->renderString('{{ phpinfo() }}', $this->ctx))->toBe('');
});

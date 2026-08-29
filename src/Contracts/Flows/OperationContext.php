<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

final readonly class OperationContext
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private StudioFlow $flow,
        private StudioFlowRun $run,
        private DataChain $dataChain,
        private array $config,
        private string $tenantId,
    ) {}

    public function flow(): StudioFlow
    {
        return $this->flow;
    }

    public function run(): StudioFlowRun
    {
        return $this->run;
    }

    public function dataChain(): DataChain
    {
        return $this->dataChain;
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return $this->config;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Interpolate a template string using `{{ $key.nested.path }}` syntax
     * against the data chain bag.
     */
    public function interpolate(string $template): string
    {
        $bag = $this->dataChain->toArray();

        return preg_replace_callback(
            '/\{\{\s*([^}]+?)\s*\}\}/',
            function (array $m) use ($bag): string {
                $path = trim($m[1]);

                if (! preg_match('/^[A-Za-z_$][A-Za-z0-9_.$]*$/', $path)) {
                    return '';
                }

                $segments = explode('.', $path);
                $head = array_shift($segments);
                $node = $bag[$head] ?? null;

                foreach ($segments as $segment) {
                    if (! is_array($node) || ! array_key_exists($segment, $node)) {
                        return '';
                    }
                    $node = $node[$segment];
                }

                if ($node === null) {
                    return '';
                }
                if (is_scalar($node)) {
                    return (string) $node;
                }

                return json_encode($node, JSON_UNESCAPED_SLASHES) ?: '';
            },
            $template,
        ) ?? $template;
    }
}

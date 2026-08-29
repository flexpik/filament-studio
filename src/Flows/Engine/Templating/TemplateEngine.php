<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine\Templating;

use Flexpik\FilamentStudio\Flows\Engine\FlowContext;

class TemplateEngine
{
    private const PATTERN = '/\{\{\s*([^}]+?)\s*\}\}/';

    public function renderString(string $template, FlowContext $context): string
    {
        return preg_replace_callback(
            self::PATTERN,
            fn (array $m) => $this->resolve(trim($m[1]), $context->toArray()),
            $template,
        ) ?? $template;
    }

    /**
     * @param  array<int|string, mixed>  $config
     * @return array<int|string, mixed>
     */
    public function renderArray(array $config, FlowContext $context): array
    {
        return array_map(function (mixed $value) use ($context) {
            if (is_array($value)) {
                return $this->renderArray($value, $context);
            }
            if (is_string($value)) {
                return $this->renderString($value, $context);
            }

            return $value;
        }, $config);
    }

    private function resolve(string $path, array $bag): string
    {
        if (! preg_match('/^[A-Za-z_$][A-Za-z0-9_.\$]*$/', $path)) {
            return '';
        }

        $segments = explode('.', $path);
        $head = array_shift($segments);
        $node = $bag[$head] ?? null;

        foreach ($segments as $segment) {
            if (is_array($node) && array_key_exists($segment, $node)) {
                $node = $node[$segment];

                continue;
            }

            return '';
        }

        if ($node === null) {
            return '';
        }
        if (is_scalar($node)) {
            return (string) $node;
        }

        return json_encode($node, JSON_UNESCAPED_SLASHES) ?: '';
    }
}

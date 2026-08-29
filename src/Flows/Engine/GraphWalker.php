<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

class GraphWalker
{
    /**
     * @param  array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}  $graph
     * @return array<int, array<string, mixed>> topologically ordered operation nodes (trigger excluded)
     */
    public function order(array $graph): array
    {
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');
        $edges = collect($graph['edges'] ?? []);
        $incoming = [];

        foreach ($nodes as $id => $_) {
            $incoming[$id] = 0;
        }
        foreach ($edges as $e) {
            $incoming[$e['target']] = ($incoming[$e['target']] ?? 0) + 1;
        }

        $queue = collect($incoming)->filter(fn ($n) => $n === 0)->keys()->all();
        $order = [];

        while ($queue !== []) {
            $id = array_shift($queue);
            $order[] = $nodes[$id];

            foreach ($edges->where('source', $id) as $edge) {
                $incoming[$edge['target']]--;
                if ($incoming[$edge['target']] === 0) {
                    $queue[] = $edge['target'];
                }
            }
        }

        return collect($order)->where('type', 'operation')->values()->all();
    }

    /**
     * @param  array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}  $graph
     * @return array<int, string>
     */
    public function successors(string $sourceId, ?string $branch, array $graph): array
    {
        $matches = collect($graph['edges'] ?? [])->where('source', $sourceId);
        if ($branch !== null) {
            $matches = $matches->where('sourceHandle', $branch);
        }

        return $matches->pluck('target')->all();
    }

    /**
     * @param  array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}  $graph
     * @return array{0: ?array<string, mixed>, 1: array<string, array<string, mixed>>}
     */
    public function indexes(array $graph): array
    {
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id')->all();
        $trigger = collect($nodes)->firstWhere('type', 'trigger');

        return [$trigger, $nodes];
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Illuminate\Support\Facades\Cache;

class EventSubscriptionRegistry
{
    private const CACHE_KEY = 'studio.flows.collection_event_subscriptions';

    public function subscribe(string $flowId, string $collectionSlug, array $events, string $versionId): void
    {
        $all = $this->all();
        $all[$flowId] = compact('collectionSlug', 'events', 'versionId');
        Cache::forever(self::CACHE_KEY, $all);
    }

    public function unsubscribe(string $flowId): void
    {
        $all = $this->all();
        unset($all[$flowId]);
        Cache::forever(self::CACHE_KEY, $all);
    }

    /** @return array<int, string> */
    public function matching(string $collectionSlug, string $event): array
    {
        return collect($this->all())
            ->filter(fn ($entry) => $entry['collectionSlug'] === $collectionSlug && in_array($event, $entry['events'], true))
            ->keys()
            ->all();
    }

    /** @return array<string, array{collectionSlug: string, events: array<int, string>, versionId: string}> */
    public function all(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }
}

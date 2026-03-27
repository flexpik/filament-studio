<?php

namespace Flexpik\FilamentStudio\Resources\DynamicCollectionResource\Concerns;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Illuminate\Support\Facades\URL;

trait ResolvesCollection
{
    public string $collectionSlug = '';

    protected ?StudioCollection $resolvedCollection = null;

    /**
     * Static cache so the slug persists across Livewire re-renders
     * within the same request/test lifecycle.
     */
    protected static ?string $cachedCollectionSlug = null;

    /**
     * Runs on every Livewire request (mount + subsequent updates).
     * Ensures URL defaults are set so Filament can generate correct URLs.
     */
    public function bootResolvesCollection(): void
    {
        if ($this->collectionSlug) {
            $this->ensureRouteHasCollectionSlug();
            DynamicCollectionResource::resolveCollection($this->collectionSlug);
        }
    }

    protected function getResolvedCollection(): StudioCollection
    {
        if (! $this->resolvedCollection) {
            $this->resolvedCollection = DynamicCollectionResource::resolveCollection($this->collectionSlug ?: null);
        }

        return $this->resolvedCollection;
    }

    /**
     * Ensure URL defaults include collection_slug so Filament can generate
     * correct URLs during Livewire updates (where the original route
     * parameter is no longer on the request).
     */
    protected function ensureRouteHasCollectionSlug(): void
    {
        if ($this->collectionSlug) {
            URL::defaults(['collection_slug' => $this->collectionSlug]);

            $route = request()->route();

            if ($route && ! $route->parameter('collection_slug')) {
                $route->setParameter('collection_slug', $this->collectionSlug);
            }
        }
    }

    protected function initializeCollectionSlug(): void
    {
        $this->collectionSlug = request()->route('collection_slug', '') ?: $this->collectionSlug ?: static::$cachedCollectionSlug ?? '';
        static::$cachedCollectionSlug = $this->collectionSlug ?: null;
        $this->ensureRouteHasCollectionSlug();
        $this->resolvedCollection = DynamicCollectionResource::resolveCollection($this->collectionSlug ?: null);
    }
}

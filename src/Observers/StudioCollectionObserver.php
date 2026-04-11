<?php

namespace Flexpik\FilamentStudio\Observers;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Support\PermissionRegistrar;

class StudioCollectionObserver
{
    public function created(StudioCollection $collection): void
    {
        PermissionRegistrar::syncForCollection($collection);
    }

    public function updated(StudioCollection $collection): void
    {
        if ($collection->wasChanged('slug')) {
            $oldSlug = $collection->getOriginal('slug');
            $oldCollection = new StudioCollection(['slug' => $oldSlug]);
            PermissionRegistrar::removeForCollection($oldCollection);
        }

        PermissionRegistrar::syncForCollection($collection);
    }

    public function deleted(StudioCollection $collection): void
    {
        PermissionRegistrar::removeForCollection($collection);
    }
}

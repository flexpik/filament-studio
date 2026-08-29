<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FlowCollection extends ResourceCollection
{
    public $collects = FlowResource::class;
}

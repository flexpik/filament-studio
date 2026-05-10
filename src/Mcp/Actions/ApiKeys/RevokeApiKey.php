<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\ApiKeys;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioApiKey;

class RevokeApiKey
{
    public function __invoke(int $id, ?int $tenantId): StudioApiKey
    {
        $key = StudioApiKey::query()->forTenant($tenantId)->find($id);
        if ($key === null) {
            throw new StudioNotFoundException('api_key', (string) $id);
        }
        $key->forceFill(['is_active' => false])->save();

        return $key;
    }
}

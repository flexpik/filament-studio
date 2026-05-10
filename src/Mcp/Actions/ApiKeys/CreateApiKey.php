<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\ApiKeys;

use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateApiKey
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{key: StudioApiKey, secret: string}
     */
    public function __invoke(array $input, int $tenantId): array
    {
        $data = Validator::validate($input, [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $secret = 'sk_live_'.Str::random(40);

        $key = StudioApiKey::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'key' => hash('sha256', $secret),
            'permissions' => $data['permissions'] ?? [],
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return ['key' => $key, 'secret' => $secret];
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\ConfirmTokens;

use Illuminate\Support\Str;

class ConfirmTokenIssuer
{
    public function __construct(private readonly ConfirmTokenStore $store) {}

    /**
     * @param  array<string, mixed>  $target
     * @return array{token: string, expires_at: string}
     */
    public function issue(string $operation, array $target, ?int $tenantId): array
    {
        $ttl = (int) config('filament-studio.mcp.confirm_token_ttl', 300);
        $token = 'ct_'.Str::ulid()->toBase32();

        $this->store->put($token, $operation, $target, $tenantId, $ttl);

        return [
            'token' => $token,
            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\ConfirmTokens;

use Flexpik\FilamentStudio\Mcp\Exceptions\ConfirmTokenInvalidException;
use Illuminate\Support\Facades\Cache;

class ConfirmTokenStore
{
    /**
     * @param  array<string, mixed>  $target
     */
    public function put(string $token, string $operation, array $target, int $tenantId, int $ttlSeconds): void
    {
        Cache::put(
            $this->key($tenantId, $token),
            [
                'operation' => $operation,
                'target' => $target,
                'tenant_id' => $tenantId,
                'issued_at' => now()->toIso8601String(),
            ],
            $ttlSeconds,
        );
    }

    /**
     * @param  array<string, mixed>  $expectedTarget
     * @return array<string, mixed>
     *
     * @throws ConfirmTokenInvalidException
     */
    public function consume(string $token, string $expectedOperation, array $expectedTarget, int $tenantId): array
    {
        $key = $this->key($tenantId, $token);
        $payload = Cache::get($key);

        if ($payload === null) {
            throw ConfirmTokenInvalidException::expired($token);
        }

        if ($payload['operation'] !== $expectedOperation || $payload['target'] !== $expectedTarget || $payload['tenant_id'] !== $tenantId) {
            throw ConfirmTokenInvalidException::mismatched($token);
        }

        Cache::forget($key);

        return $payload;
    }

    private function key(int $tenantId, string $token): string
    {
        return sprintf('studio:mcp:confirm:%d:%s', $tenantId, $token);
    }
}

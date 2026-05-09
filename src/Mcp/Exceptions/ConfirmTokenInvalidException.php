<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use RuntimeException;

class ConfirmTokenInvalidException extends RuntimeException
{
    private function __construct(public readonly string $mcpCode, public readonly string $token, string $message)
    {
        parent::__construct($message);
    }

    public static function expired(string $token): self
    {
        return new self('EXPIRED_CONFIRM_TOKEN', $token, 'Confirm token has expired.');
    }

    public static function mismatched(string $token): self
    {
        return new self('INVALID_CONFIRM_TOKEN', $token, 'Confirm token does not match the requested operation.');
    }

    public static function consumed(string $token): self
    {
        return new self('CONSUMED_CONFIRM_TOKEN', $token, 'Confirm token has already been used.');
    }

    /** @return array<string, mixed> */
    public function mcpData(): array
    {
        return [
            'token' => $this->token,
            'next_action' => 'call preview_* again',
        ];
    }
}

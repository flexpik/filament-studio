<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Flexpik\FilamentStudio\Models\StudioApiKey;
use RuntimeException;

class StudioApiKeyContext
{
    protected ?StudioApiKey $key = null;

    public function set(StudioApiKey $key): void
    {
        $this->key = $key;
    }

    public function clear(): void
    {
        $this->key = null;
    }

    public function current(): ?StudioApiKey
    {
        return $this->key;
    }

    public function require(): StudioApiKey
    {
        if ($this->key === null) {
            throw new RuntimeException('Studio API key is not bound to the current request context.');
        }

        return $this->key;
    }
}

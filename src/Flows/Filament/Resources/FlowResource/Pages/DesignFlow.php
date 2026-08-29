<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Resources\Pages\Page;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DesignFlow extends Page
{
    protected static string $resource = FlowResource::class;

    protected string $view = 'filament-studio::flows.pages.design-flow';

    public string|StudioFlow|null $record = null;

    public string $canvasApiKey = '';

    public function mount(string $record): void
    {
        Gate::authorize('update', $r = StudioFlow::findOrFail($record));
        $this->record = $r;
        $this->canvasApiKey = $this->mintCanvasApiKey();
    }

    public function getApiBase(): string
    {
        return '/'.ltrim((string) config('filament-studio.api.prefix', 'api/studio'), '/');
    }

    private function mintCanvasApiKey(): string
    {
        // Clean up any existing canvas-session keys for this user to prevent unbounded growth.
        StudioApiKey::query()
            ->where('name', 'Canvas session ('.auth()->id().')')
            ->delete();

        $plain = Str::random(48);

        StudioApiKey::create([
            'tenant_id' => null,
            'name' => 'Canvas session ('.auth()->id().')',
            'key' => hash('sha256', $plain),
            'permissions' => ['_studio' => ['flows']],
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        return $plain;
    }
}

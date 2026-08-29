<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ViewFlowRun extends Page
{
    protected static string $resource = FlowResource::class;

    protected string $view = 'filament-studio::flows.pages.view-flow-run';

    public string|StudioFlow|null $record = null;

    public string|StudioFlowRun|null $run = null;

    public function mount(string $record, string $runId): void
    {
        $this->record = StudioFlow::findOrFail($record);
        $this->run = $this->record->runs()->with('steps')->findOrFail($runId);
    }

    /**
     * Return steps grouped by operation_key, sorted by started_at, with masked I/O.
     * Each group is an array of attempts (sorted by attempt_number).
     *
     * @return Collection<string, Collection<int, object>>
     */
    public function getGroupedStepsProperty(): Collection
    {
        if (! $this->run instanceof StudioFlowRun) {
            return collect();
        }

        $masker = app(MasksSensitiveValues::class);

        return $this->run->steps
            ->sortBy('started_at')
            ->map(function ($step) use ($masker) {
                $step->masked_input = $step->input ? $masker->mask((array) $step->input) : null;
                $step->masked_output = $step->output ? $masker->mask((array) $step->output) : null;

                return $step;
            })
            ->groupBy('operation_key');
    }

    public function isAdminUser(): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('studio-admin');
        }

        return $user->can('manage_studio');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->run instanceof StudioFlowRun) {
            if ($this->run->flow_version_id !== null) {
                $version = $this->run->flowVersion?->version;

                return $version ? "Ran on version v{$version}" : null;
            }

            return 'Ran on draft (inline snapshot)';
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rerun')
                ->label('Re-run with same payload')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => auth()->user()?->can('run_flows'))
                ->action(function (FlowDispatcher $dispatcher) {
                    $dispatcher->dispatchAsync(
                        flow: $this->record,
                        triggerType: $this->run->trigger_type,
                        payload: $this->run->trigger_payload ?? [],
                        accountability: ['user_id' => auth()->id(), 'tenant_id' => $this->record->tenant_id, 'role' => null, 'source' => 'manual'],
                    );
                    Notification::make()->success()->title('Re-run dispatched')->send();
                }),
            Action::make('replay')
                ->label('Replay')
                ->icon('heroicon-o-play-circle')
                ->visible(fn () => auth()->user()?->can('run_flows'))
                ->disabled(fn () => $this->record instanceof StudioFlow && $this->record->published_version_id === null)
                ->requiresConfirmation(fn () => $this->run instanceof StudioFlowRun
                    && $this->record instanceof StudioFlow
                    && $this->run->flow_version_id !== null
                    && $this->run->flow_version_id !== $this->record->published_version_id)
                ->modalHeading('Replay against current version?')
                ->modalDescription('This run used a different version than the currently published version. Replaying will use the current published version.')
                ->action(function (FlowDispatcher $dispatcher) {
                    $dispatcher->dispatchAsync(
                        flow: $this->record,
                        triggerType: $this->run->trigger_type,
                        payload: $this->run->trigger_payload ?? [],
                        accountability: ['user_id' => auth()->id(), 'tenant_id' => $this->record->tenant_id, 'role' => null, 'source' => 'manual'],
                    );
                    Notification::make()->success()->title('Replay dispatched')->send();
                }),
            Action::make('cancel')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->run instanceof StudioFlowRun && ! $this->run->status->isTerminal())
                ->requiresConfirmation()
                ->action(function () {
                    $this->run->forceFill([
                        'status' => FlowRunStatus::Cancelled,
                        'finished_at' => now(),
                    ])->save();
                    $this->run->refresh();
                    Notification::make()->success()->title('Run cancelled')->send();
                }),
        ];
    }
}

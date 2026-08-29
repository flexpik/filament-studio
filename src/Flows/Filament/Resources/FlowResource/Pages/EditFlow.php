<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers\VersionsRelationManager;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RotateWebhookSecret;
use Illuminate\Contracts\Support\Htmlable;

class EditFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->record;
        if ($record->draft_graph !== null) {
            return 'Draft';
        }
        if ($record->published_version_id !== null) {
            return 'Published v'.($record->publishedVersion?->version ?? '?');
        }

        return 'Unpublished';
    }

    public function getRelationManagers(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-arrow-up-circle')
                ->visible(fn () => auth()->user()?->can('publish_flows'))
                ->disabled(fn () => $this->record->draft_graph === null)
                ->form([
                    Textarea::make('change_summary')
                        ->label('Change summary')
                        ->nullable()
                        ->maxLength(255),
                ])
                ->action(function (array $data, PublishFlowVersion $service) {
                    $service->publish(
                        $this->record->fresh(),
                        $data['change_summary'] ?? null,
                        (string) auth()->id(),
                    );
                    $this->refreshFormData([]);
                    Notification::make()->success()->title('Flow published')->send();
                }),
            Action::make('rotate_webhook_secret')
                ->label('Rotate webhook secret')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasRole('studio-admin'))
                ->requiresConfirmation()
                ->action(function (RotateWebhookSecret $service) {
                    $newSecret = $service->rotate($this->record->fresh(), auth()->user());
                    Notification::make()
                        ->title('Webhook secret rotated')
                        ->body('New secret: '.$newSecret.' (copy it now, it will not be shown again)')
                        ->success()
                        ->send();
                }),
            Action::make('run')
                ->label('Run Now')
                ->icon('heroicon-o-play')
                ->visible(fn () => auth()->user()?->can('run_flows'))
                ->disabled(fn () => $this->record->status !== FlowStatus::Active)
                ->action(function (FlowDispatcher $dispatcher) {
                    if ($this->record->status !== FlowStatus::Active) {
                        Notification::make()->warning()->title('Flow is not active')->send();

                        return;
                    }

                    if ($this->record->published_version_id === null) {
                        Notification::make()->warning()->title('Flow has no published version')->send();

                        return;
                    }

                    $dispatcher->dispatchAsync(
                        flow: $this->record,
                        triggerType: 'manual',
                        payload: [],
                        accountability: ['user_id' => auth()->id(), 'tenant_id' => $this->record->tenant_id, 'role' => null, 'source' => 'manual'],
                    );
                    Notification::make()->success()->title('Flow dispatched')->send();
                }),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

class ListFlowRuns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FlowResource::class;

    protected string $view = 'filament-studio::flows.pages.list-flow-runs';

    public string|StudioFlow|null $record = null;

    public function mount(string $record): void
    {
        $this->record = StudioFlow::findOrFail($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportRuns')
                ->label('Export runs')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()?->can('export_flows'))
                ->form([
                    DatePicker::make('from')->required(),
                    DatePicker::make('to')->required(),
                    Select::make('format')
                        ->options(['jsonl' => 'JSONL', 'csv' => 'CSV'])
                        ->default('jsonl')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $flowId = $this->record instanceof StudioFlow ? $this->record->id : $this->record;
                    $prefix = config('filament-studio.api.prefix', 'api/studio');
                    $url = url("/{$prefix}/flows/{$flowId}/runs/export?format={$data['format']}&from={$data['from']}&to={$data['to']}");

                    return redirect($url);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StudioFlowRun::query()->where('flow_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('trigger_type'),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->since(),
                Tables\Columns\TextColumn::make('duration_ms')->suffix(' ms'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function (StudioFlowRun $run) {
                        $flowId = $this->record->id;

                        return FlowResource::getUrl('view-run', ['record' => $flowId, 'runId' => $run->id]);
                    }),
            ])
            ->defaultSort('started_at', 'desc')
            ->poll('5s');
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit log';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('event')->badge(),
                TextColumn::make('actor_type')->label('Actor'),
                TextColumn::make('actor_id')->label('Actor ID')->limit(20),
                TextColumn::make('ip_address'),
                TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ]);
    }
}

<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers\AuditLogsRelationManager;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;

it('FlowResource::getRelations() includes AuditLogsRelationManager', function () {
    expect(FlowResource::getRelations())->toContain(AuditLogsRelationManager::class);
});

it('AuditLogsRelationManager has correct relationship name', function () {
    expect(AuditLogsRelationManager::getRelationshipName())->toBe('auditLogs');
});

it('StudioFlow has auditLogs relation returning StudioFlowAuditLog', function () {
    $flow = StudioFlow::factory()->create();
    StudioFlowAuditLog::factory()->count(2)->create(['flow_id' => $flow->id]);
    expect($flow->auditLogs()->count())->toBeGreaterThanOrEqual(2);
});

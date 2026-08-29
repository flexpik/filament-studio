<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the studio_flow_audit_log table with required columns', function () {
    expect(Schema::hasTable('studio_flow_audit_log'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'id'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'flow_id'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'actor_id'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'actor_type'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'event'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'metadata'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'ip_address'))->toBeTrue();
    expect(Schema::hasColumn('studio_flow_audit_log', 'created_at'))->toBeTrue();
});

it('does not have an updated_at column', function () {
    expect(Schema::hasColumn('studio_flow_audit_log', 'updated_at'))->toBeFalse();
});

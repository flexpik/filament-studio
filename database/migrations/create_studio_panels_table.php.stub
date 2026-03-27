<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix.'panels', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('dashboard_id')
                ->nullable()
                ->constrained($prefix.'dashboards')
                ->cascadeOnDelete();
            $table->string('placement', 32);
            $table->foreignId('context_collection_id')
                ->nullable()
                ->constrained($prefix.'collections')
                ->cascadeOnDelete();
            $table->string('panel_type', 64);

            // Header options
            $table->boolean('header_visible')->default(true);
            $table->string('header_label', 128)->nullable();
            $table->string('header_icon', 64)->nullable();
            $table->string('header_color', 32)->nullable();
            $table->text('header_note')->nullable();

            // Grid (dashboard placement)
            $table->tinyInteger('grid_col_span')->unsigned()->default(6);
            $table->tinyInteger('grid_row_span')->unsigned()->default(4);
            $table->smallInteger('grid_order')->default(0);

            // Resource placements (stacked)
            $table->smallInteger('sort_order')->default(0);

            // Panel-type-specific config
            $table->json('config')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'placement', 'context_collection_id'], 'idx_placement');
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix.'panels');
    }
};

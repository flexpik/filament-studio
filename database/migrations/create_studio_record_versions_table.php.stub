<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'record_versions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('record_id')
                ->constrained($prefix . 'records')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'record_versions');
    }
};

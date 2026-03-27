<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'records', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('collection_id')
                ->constrained($prefix . 'collections')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'collection_id']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'records');
    }
};

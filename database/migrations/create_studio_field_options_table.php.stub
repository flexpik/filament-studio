<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'field_options', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('field_id')
                ->constrained($prefix . 'fields')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('value', 128);
            $table->string('label', 255);
            $table->string('color', 32)->nullable();
            $table->string('icon', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'field_options');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'fields', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained($prefix . 'collections')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('column_name', 64);
            $table->string('label', 128);
            $table->string('field_type', 64);
            $table->string('eav_cast')->default('text');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_nullable')->default(true);
            $table->boolean('is_indexed')->default(false);
            $table->boolean('is_system')->default(false);
            $table->text('default_value')->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->string('hint', 255)->nullable();
            $table->string('hint_icon', 64)->nullable();
            $table->string('width')->default('full');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_hidden_in_form')->default(false);
            $table->boolean('is_hidden_in_table')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_disabled_on_create')->default(false);
            $table->boolean('is_disabled_on_edit')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('settings')->nullable();
            $table->json('translations')->nullable();
            $table->json('auto_fill_on')->nullable();
            $table->string('auto_fill_value', 128)->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'column_name']);
            $table->index(['collection_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'fields');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'values', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('record_id')
                ->constrained($prefix . 'records')
                ->cascadeOnDelete();
            $table->foreignId('field_id')
                ->constrained($prefix . 'fields')
                ->cascadeOnDelete();
            $table->text('val_text')->nullable();
            $table->bigInteger('val_integer')->nullable();
            $table->decimal('val_decimal', 20, 6)->nullable();
            $table->boolean('val_boolean')->nullable();
            $table->dateTime('val_datetime')->nullable();
            $table->json('val_json')->nullable();

            $table->unique(['record_id', 'field_id']);
            $table->index(['field_id', 'val_integer']);
            $table->index(['field_id', 'val_decimal']);
            $table->index(['field_id', 'val_boolean']);
            $table->index(['field_id', 'val_datetime']);
        });

        // MySQL prefix index for text values
        if (config('database.default') === 'mysql') {
            $tableName = config('filament-studio.table_prefix', 'studio_') . 'values';
            \Illuminate\Support\Facades\DB::statement(
                "CREATE INDEX {$tableName}_field_val_text_index ON {$tableName} (field_id, val_text(64))"
            );
        }
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'values');
    }
};

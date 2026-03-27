<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create("{$prefix}saved_filters", function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained("{$prefix}collections")
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('name');
            $table->boolean('is_shared')->default(false);
            $table->json('filter_tree');
            $table->timestamps();

            $table->unique(['collection_id', 'tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists("{$prefix}saved_filters");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        Schema::create($prefix . 'collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name', 64);
            $table->string('label', 128);
            $table->string('label_plural', 128);
            $table->string('slug', 64);
            $table->string('icon', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_singleton')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('api_enabled')->default(false);
            $table->string('sort_field', 64)->nullable();
            $table->string('sort_direction')->default('asc');
            $table->boolean('enable_versioning')->default(false);
            $table->boolean('enable_soft_deletes')->default(false);
            $table->string('archive_field', 64)->nullable();
            $table->string('archive_value', 64)->nullable();
            $table->string('display_template', 255)->nullable();
            $table->json('translations')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        Schema::dropIfExists($prefix . 'collections');
    }
};

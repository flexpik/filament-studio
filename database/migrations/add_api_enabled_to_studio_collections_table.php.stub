<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        $table = $prefix.'collections';

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'api_enabled')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->boolean('api_enabled')->default(false)->after('is_hidden');
        });
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');
        $table = $prefix.'collections';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'api_enabled')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('api_enabled');
        });
    }
};

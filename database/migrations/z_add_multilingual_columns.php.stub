<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        if (Schema::hasTable($prefix.'fields') && ! Schema::hasColumn($prefix.'fields', 'is_translatable')) {
            Schema::table($prefix.'fields', function (Blueprint $table) {
                $table->boolean('is_translatable')->default(false)->after('is_disabled_on_edit');
            });
        }

        if (Schema::hasTable($prefix.'collections') && ! Schema::hasColumn($prefix.'collections', 'supported_locales')) {
            Schema::table($prefix.'collections', function (Blueprint $table) {
                $table->json('supported_locales')->nullable()->after('translations');
                $table->string('default_locale', 10)->nullable()->after('supported_locales');
            });
        }

        if (Schema::hasTable($prefix.'values') && ! Schema::hasColumn($prefix.'values', 'locale')) {
            // Add locale column to values table
            Schema::table($prefix.'values', function (Blueprint $table) {
                $table->string('locale', 10)->default('en')->after('field_id');
            });

            // Drop old unique index and create new composite unique (separate call for SQLite compatibility).
            // On MySQL, the foreign key on record_id uses the unique index as its backing index,
            // so we must drop the FK first, then the unique, then recreate both.
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                Schema::table($prefix.'values', function (Blueprint $table) use ($prefix) {
                    $table->dropForeign("{$prefix}values_record_id_foreign");
                    $table->dropUnique("{$prefix}values_record_id_field_id_unique");
                    $table->unique(['record_id', 'field_id', 'locale'], "{$prefix}values_record_field_locale_unique");
                    $table->foreign('record_id', "{$prefix}values_record_id_foreign")
                        ->references('id')
                        ->on($prefix.'records')
                        ->cascadeOnDelete();
                });
            } else {
                Schema::table($prefix.'values', function (Blueprint $table) use ($prefix) {
                    $table->dropUnique("{$prefix}values_record_id_field_id_unique");
                    $table->unique(['record_id', 'field_id', 'locale'], "{$prefix}values_record_field_locale_unique");
                });
            }
        }
    }

    public function down(): void
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        if (Schema::hasTable($prefix.'values') && Schema::hasColumn($prefix.'values', 'locale')) {
            // Remove non-default locale rows so the original unique(record_id, field_id) can be restored
            DB::table($prefix.'values')
                ->where('locale', '!=', config('filament-studio.locales.default', 'en'))
                ->delete();

            // Drop new unique index and restore old one (separate call for SQLite compatibility)
            Schema::table($prefix.'values', function (Blueprint $table) use ($prefix) {
                $table->dropUnique("{$prefix}values_record_field_locale_unique");
                $table->unique(['record_id', 'field_id'], "{$prefix}values_record_id_field_id_unique");
            });

            Schema::table($prefix.'values', function (Blueprint $table) {
                $table->dropColumn('locale');
            });
        }

        if (Schema::hasTable($prefix.'collections') && Schema::hasColumn($prefix.'collections', 'supported_locales')) {
            Schema::table($prefix.'collections', function (Blueprint $table) {
                $table->dropColumn(['supported_locales', 'default_locale']);
            });
        }

        if (Schema::hasTable($prefix.'fields') && Schema::hasColumn($prefix.'fields', 'is_translatable')) {
            Schema::table($prefix.'fields', function (Blueprint $table) {
                $table->dropColumn('is_translatable');
            });
        }
    }
};

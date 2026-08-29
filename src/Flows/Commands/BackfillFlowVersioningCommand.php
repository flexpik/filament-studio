<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFlowVersioningCommand extends Command
{
    protected $signature = 'studio:flows:backfill-versioning';

    protected $description = 'Back-fill published_version_id on flows from their latest published version';

    public function handle(): int
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        DB::table($prefix.'flows')
            ->whereNull('published_version_id')
            ->orderBy('id')
            ->each(function ($flow) use ($prefix) {
                $latest = DB::table($prefix.'flow_versions')
                    ->where('flow_id', $flow->id)
                    ->whereNotNull('published_at')
                    ->orderByDesc('version')
                    ->first();
                if ($latest) {
                    DB::table($prefix.'flows')->where('id', $flow->id)->update([
                        'published_version_id' => $latest->id,
                    ]);
                }
            });

        $this->info('Back-fill complete.');

        return self::SUCCESS;
    }
}

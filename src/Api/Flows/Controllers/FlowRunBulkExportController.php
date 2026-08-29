<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Controllers;

use Carbon\Carbon;
use Flexpik\FilamentStudio\Api\Flows\Requests\RunFlowRequest;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FlowRunBulkExportController
{
    public function export(RunFlowRequest $request, string $flowId): StreamedResponse|JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);

        $from = $request->input('from');
        $to = $request->input('to');

        if (! $from || ! $to) {
            return response()->json([
                'errors' => ['date_range' => ['Both from and to dates are required']],
            ], 422);
        }

        try {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
        } catch (\Throwable) {
            return response()->json([
                'errors' => ['date_range' => ['Invalid date format']],
            ], 422);
        }

        $maxDays = (int) config('filament-studio.flows.export.max_range_days', 30);

        if ($fromDate->diffInDays($toDate) > $maxDays) {
            return response()->json([
                'errors' => ['date_range' => ["Date range must not exceed {$maxDays} days"]],
            ], 422);
        }

        $format = $request->input('format', 'jsonl');

        if ($format === 'csv') {
            return $this->streamCsv($flow, $fromDate->toDateTimeString(), $toDate->toDateTimeString());
        }

        return $this->streamJsonl($flow, $fromDate->toDateTimeString(), $toDate->toDateTimeString());
    }

    private function streamJsonl(StudioFlow $flow, string $from, string $to): StreamedResponse
    {
        $masker = app(MasksSensitiveValues::class);

        return response()->stream(function () use ($flow, $from, $to, $masker) {
            $query = StudioFlowRun::query()
                ->with('steps')
                ->where('flow_id', $flow->id)
                ->where('dry_run', false)
                ->whereBetween('started_at', [$from, $to])
                ->orderBy('started_at')
                ->orderBy('id')
                ->cursor();

            foreach ($query as $run) {
                echo json_encode($this->runToArray($run, $masker))."\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, ['Content-Type' => 'application/x-ndjson']);
    }

    private function streamCsv(StudioFlow $flow, string $from, string $to): StreamedResponse
    {
        return response()->stream(function () use ($flow, $from, $to) {
            $header = ['id', 'flow_id', 'status', 'started_at', 'finished_at', 'duration_ms', 'trigger_type'];
            echo implode(',', $header)."\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $query = StudioFlowRun::query()
                ->where('flow_id', $flow->id)
                ->where('dry_run', false)
                ->whereBetween('started_at', [$from, $to])
                ->orderBy('started_at')
                ->orderBy('id')
                ->cursor();

            foreach ($query as $run) {
                $row = [
                    $run->id,
                    $run->flow_id,
                    $run->status?->value,
                    $run->started_at,
                    $run->finished_at,
                    $run->duration_ms,
                    $run->trigger_type,
                ];
                echo implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) ($v ?? '')).'"', $row))."\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function runToArray(StudioFlowRun $run, MasksSensitiveValues $masker): array
    {
        return [
            'id' => $run->id,
            'flow_id' => $run->flow_id,
            'flow_version_id' => $run->flow_version_id,
            'status' => $run->status?->value,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
            'duration_ms' => $run->duration_ms,
            'trigger_type' => $run->trigger_type,
            'trigger_payload' => $masker->mask((array) ($run->trigger_payload ?? [])),
            'steps' => $run->steps->map(fn ($step) => [
                'id' => $step->id,
                'operation_key' => $step->operation_key,
                'operation_type' => $step->operation_type,
                'attempt_number' => $step->attempt_number,
                'status' => $step->status?->value,
                'input' => $masker->mask((array) ($step->input ?? [])),
                'output' => $masker->mask((array) ($step->output ?? [])),
                'error_class' => $step->error_class,
                'error_message' => $step->error_message,
                'started_at' => $step->started_at,
                'finished_at' => $step->finished_at,
            ])->values()->toArray(),
        ];
    }

    private function scopedFlow(RunFlowRequest $request, string $id): StudioFlow
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        return StudioFlow::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);
    }
}

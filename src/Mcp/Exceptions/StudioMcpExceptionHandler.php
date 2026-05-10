<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudioMcpExceptionHandler
{
    /**
     * @return array{code: string, message: string, data: array<string, mixed>}
     */
    public function toResponse(Throwable $e): array
    {
        if ($e instanceof StudioMcpAuthorizationException) {
            return [
                'code' => $e->code(),
                'message' => $e->getMessage(),
                'data' => $e->data(),
            ];
        }

        if (method_exists($e, 'mcpCode') && method_exists($e, 'mcpData')) {
            return [
                'code' => $e->mcpCode(),
                'message' => $e->getMessage(),
                'data' => $e->mcpData(),
            ];
        }

        if ($e instanceof ValidationException) {
            return [
                'code' => 'STUDIO_VALIDATION_FAILED',
                'message' => $e->getMessage(),
                'data' => ['errors' => $e->errors()],
            ];
        }

        if ($e instanceof ModelNotFoundException) {
            return [
                'code' => 'STUDIO_NOT_FOUND',
                'message' => $e->getMessage(),
                'data' => ['resource' => class_basename($e->getModel() ?? 'Model')],
            ];
        }

        $correlationId = (string) Str::ulid();
        Log::error('[studio.mcp] unexpected exception', [
            'correlation_id' => $correlationId,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'code' => 'STUDIO_INTERNAL_ERROR',
            'message' => 'An unexpected error occurred. See logs for details.',
            'data' => ['correlation_id' => $correlationId],
        ];
    }
}

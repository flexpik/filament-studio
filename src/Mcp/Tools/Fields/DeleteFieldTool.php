<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Fields;

use Flexpik\FilamentStudio\Mcp\Actions\Fields\DeleteField;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteFieldTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_field';

    protected string $description = 'Delete a field (requires confirm_token from studio_preview_delete_field).';

    public function schema($schema): array
    {
        return [
            'collection_slug' => $schema->string()->required(),
            'column_name' => $schema->string()->required(),
            'confirm_token' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $collectionSlug = (string) $request->get('collection_slug');
            $columnName = (string) $request->get('column_name');
            $token = (string) $request->get('confirm_token');

            (new ConfirmTokenStore)->consume(
                $token,
                'delete_field',
                ['collection_slug' => $collectionSlug, 'column_name' => $columnName],
                $apiKey->tenant_id,
            );

            (new DeleteField)($collectionSlug, $columnName, $apiKey->tenant_id);

            return Response::json(['deleted' => true, 'column_name' => $columnName]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}

<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Tools\Collections;

use Flexpik\FilamentStudio\Mcp\Actions\Collections\DeleteCollection;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteCollectionTool extends Tool
{
    use ToolAuthorizes;

    protected string $name = 'studio_delete_collection';

    protected string $description = 'Delete a collection (requires confirm_token from studio_preview_delete_collection).';

    public function schema($schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'confirm_token' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->requireScope(StudioScope::ManageCollections);

            $apiKey = app(StudioApiKeyContext::class)->require();
            $slug = (string) $request->get('slug');
            $token = (string) $request->get('confirm_token');

            (new ConfirmTokenStore)->consume(
                $token,
                'delete_collection',
                ['slug' => $slug],
                $apiKey->tenant_id,
            );

            (new DeleteCollection)($slug, $apiKey->tenant_id);

            return Response::json(['deleted' => true, 'slug' => $slug]);
        } catch (\Throwable $e) {
            return Response::json((new StudioMcpExceptionHandler)->toResponse($e));
        }
    }
}

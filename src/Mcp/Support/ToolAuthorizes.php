<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpAuthorizationException;

trait ToolAuthorizes
{
    protected function requireScope(StudioScope $scope): void
    {
        $key = app(StudioApiKeyContext::class)->current();

        if ($key === null) {
            throw new StudioMcpAuthorizationException(
                'No Studio API key is bound to the current request.',
                ['required_scope' => $scope->value, 'granted_scopes' => []],
            );
        }

        if (! $key->canManage($scope)) {
            $granted = array_map(
                fn (string $name) => '_studio.'.$name,
                $key->permissions['_studio'] ?? [],
            );

            throw new StudioMcpAuthorizationException(
                "API key lacks the required scope: {$scope->value}",
                ['required_scope' => $scope->value, 'granted_scopes' => $granted],
            );
        }
    }

    protected function requireCollectionAction(string $collectionSlug, ApiAction $action): void
    {
        $key = app(StudioApiKeyContext::class)->current();

        if ($key === null) {
            throw new StudioMcpAuthorizationException(
                'No Studio API key is bound to the current request.',
                ['required_action' => $action->value, 'collection' => $collectionSlug],
            );
        }

        if (! $key->can($collectionSlug, $action)) {
            throw new StudioMcpAuthorizationException(
                "API key cannot perform '{$action->value}' on collection '{$collectionSlug}'.",
                ['required_action' => $action->value, 'collection' => $collectionSlug],
            );
        }
    }
}

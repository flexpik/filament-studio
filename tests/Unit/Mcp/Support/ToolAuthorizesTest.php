<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpAuthorizationException;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Mcp\Support\ToolAuthorizes;
use Flexpik\FilamentStudio\Models\StudioApiKey;

class FakeTool
{
    use ToolAuthorizes;

    public function callRequireScope(StudioScope $scope): void
    {
        $this->requireScope($scope);
    }

    public function callRequireCollectionAction(string $slug, ApiAction $action): void
    {
        $this->requireCollectionAction($slug, $action);
    }
}

it('throws when no API key is bound', function () {
    app(StudioApiKeyContext::class)->clear();

    expect(fn () => (new FakeTool)->callRequireScope(StudioScope::ManageCollections))
        ->toThrow(StudioMcpAuthorizationException::class);
});

it('passes when the bound key holds the required scope', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['_studio' => ['manage_collections']],
    ]);
    app(StudioApiKeyContext::class)->set($key);

    (new FakeTool)->callRequireScope(StudioScope::ManageCollections);

    expect(true)->toBeTrue(); // no exception
});

it('throws with required_scope and granted_scopes data when scope missing', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['_studio' => ['read_schema']],
    ]);
    app(StudioApiKeyContext::class)->set($key);

    try {
        (new FakeTool)->callRequireScope(StudioScope::ManageCollections);
        expect(false)->toBeTrue('should have thrown');
    } catch (StudioMcpAuthorizationException $e) {
        expect($e->data())->toMatchArray([
            'required_scope' => '_studio.manage_collections',
            'granted_scopes' => ['_studio.read_schema'],
        ]);
    }
});

it('passes when the bound key permits the per-collection action', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['products' => ['index', 'show']],
    ]);
    app(StudioApiKeyContext::class)->set($key);

    (new FakeTool)->callRequireCollectionAction('products', ApiAction::Index);

    expect(true)->toBeTrue();
});

it('throws when per-collection action is not permitted', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['products' => ['index']],
    ]);
    app(StudioApiKeyContext::class)->set($key);

    expect(fn () => (new FakeTool)->callRequireCollectionAction('products', ApiAction::Store))
        ->toThrow(StudioMcpAuthorizationException::class);
});

<?php

namespace Flexpik\FilamentStudio\Api\OpenApi;

use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;

class StudioOperationTransformer
{
    public function __invoke(Operation $operation): void
    {
        $routeName = $operation->getAttribute('route')?->getName() ?? '';

        if (! str_contains($routeName, 'studio')) {
            return;
        }

        // Skip if X-Api-Key parameter already exists (added by DocumentTransformer)
        foreach ($operation->parameters as $param) {
            if ($param instanceof Parameter && $param->name === 'X-Api-Key') {
                return;
            }
        }

        $operation->addParameters([
            (new Parameter('X-Api-Key', 'header'))
                ->description('API key for authentication')
                ->required(true),
        ]);
    }
}

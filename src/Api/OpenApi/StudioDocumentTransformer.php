<?php

namespace Flexpik\FilamentStudio\Api\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class StudioDocumentTransformer
{
    public function __invoke(OpenApi $openApi): void
    {
        $openApi->secure(
            SecurityScheme::apiKey('header', 'X-Api-Key')
        );
    }
}

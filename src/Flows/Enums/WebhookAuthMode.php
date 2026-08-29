<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Enums;

enum WebhookAuthMode: string
{
    case Hmac = 'hmac';
    case ApiKey = 'api_key';
    case None = 'none';
}

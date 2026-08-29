<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

enum ConfigFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Select = 'select';
    case KeyValue = 'keyvalue';
    case Code = 'code';
    case FlowSelect = 'flow_select';
    case CollectionSelect = 'collection_select';
}

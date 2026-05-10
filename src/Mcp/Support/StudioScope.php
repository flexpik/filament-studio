<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

enum StudioScope: string
{
    case ManageCollections = '_studio.manage_collections';
    case ManageDashboards = '_studio.manage_dashboards';
    case ManageFilters = '_studio.manage_filters';
    case ManageApiKeys = '_studio.manage_api_keys';
    case ReadSchema = '_studio.read_schema';

    public function name(): string
    {
        return substr($this->value, strlen('_studio.'));
    }

    public function label(): string
    {
        return match ($this) {
            self::ManageCollections => 'Manage Collections',
            self::ManageDashboards => 'Manage Dashboards',
            self::ManageFilters => 'Manage Saved Filters',
            self::ManageApiKeys => 'Manage API Keys',
            self::ReadSchema => 'Read Schema (Read-Only)',
        };
    }

    /** @return array<string, string> [value => label] for Filament option lists */
    public static function asSelectOptions(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}

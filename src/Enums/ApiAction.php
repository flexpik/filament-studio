<?php

namespace Flexpik\FilamentStudio\Enums;

enum ApiAction: string
{
    case Index = 'index';
    case Show = 'show';
    case Store = 'store';
    case Update = 'update';
    case Destroy = 'destroy';

    public function label(): string
    {
        return match ($this) {
            self::Index => 'List Records',
            self::Show => 'View Record',
            self::Store => 'Create Record',
            self::Update => 'Update Record',
            self::Destroy => 'Delete Record',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function asSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action) => [$action->value => $action->label()])
            ->all();
    }
}

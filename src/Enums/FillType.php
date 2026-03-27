<?php

namespace Flexpik\FilamentStudio\Enums;

enum FillType: string
{
    case Gradient = 'gradient';
    case Solid = 'solid';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Gradient => 'Gradient',
            self::Solid => 'Solid',
            self::None => 'None',
        };
    }
}

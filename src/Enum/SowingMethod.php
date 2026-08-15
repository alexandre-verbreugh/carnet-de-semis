<?php

declare(strict_types=1);

namespace App\Enum;

enum SowingMethod: string
{
    case SemisDirect = 'semis_direct';
    case Godet = 'godet';
    case PlantRepique = 'plant_repique';

    public function label(): string
    {
        return match ($this) {
            self::SemisDirect => 'Semis direct',
            self::Godet => 'Semis en godet',
            self::PlantRepique => 'Plant repiqué',
        };
    }
}

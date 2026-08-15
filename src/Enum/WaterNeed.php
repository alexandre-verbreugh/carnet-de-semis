<?php

declare(strict_types=1);

namespace App\Enum;

enum WaterNeed: string
{
    case Faible = 'faible';
    case Moyen = 'moyen';
    case Eleve = 'eleve';

    public function label(): string
    {
        return match ($this) {
            self::Faible => 'Faible',
            self::Moyen => 'Moyen',
            self::Eleve => 'Élevé',
        };
    }
}

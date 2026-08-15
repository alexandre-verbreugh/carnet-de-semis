<?php

declare(strict_types=1);

namespace App\Enum;

enum Exposure: string
{
    case PleinSoleil = 'plein_soleil';
    case MiOmbre = 'mi_ombre';
    case Ombre = 'ombre';

    public function label(): string
    {
        return match ($this) {
            self::PleinSoleil => 'Plein soleil',
            self::MiOmbre => 'Mi-ombre',
            self::Ombre => 'Ombre',
        };
    }
}

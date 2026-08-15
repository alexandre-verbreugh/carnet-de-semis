<?php

declare(strict_types=1);

namespace App\Enum;

enum SpeciesCategory: string
{
    case LegumeFeuille = 'legume_feuille';
    case LegumeRacine = 'legume_racine';
    case LegumeFruit = 'legume_fruit';
    case Legumineuse = 'legumineuse';
    case Aromatique = 'aromatique';
    case Fleur = 'fleur';

    public function label(): string
    {
        return match ($this) {
            self::LegumeFeuille => 'Légume-feuille',
            self::LegumeRacine => 'Légume-racine',
            self::LegumeFruit => 'Légume-fruit',
            self::Legumineuse => 'Légumineuse',
            self::Aromatique => 'Aromatique',
            self::Fleur => 'Fleur',
        };
    }
}

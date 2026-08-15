<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Composants pouvant entrer dans le remplissage d'une jardiniere.
 *
 * Une jardiniere en contient generalement plusieurs (melange), d'ou le stockage
 * sous forme de liste dans Planter::$substrateComponents.
 */
enum SubstrateComponent: string
{
    case TerreVegetale = 'terre_vegetale';
    case TerreDeJardin = 'terre_de_jardin';
    case TerreauSemis = 'terreau_semis';
    case TerreauUniversel = 'terreau_universel';
    case TerreauPlantation = 'terreau_plantation';
    case TourbeBlonde = 'tourbe_blonde';
    case FibreDeCoco = 'fibre_de_coco';
    case Compost = 'compost';
    case Fumier = 'fumier';
    case Sable = 'sable';
    case Perlite = 'perlite';
    case Vermiculite = 'vermiculite';
    case BillesArgile = 'billes_argile';
    case Ecorce = 'ecorce';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::TerreVegetale => 'Terre végétale',
            self::TerreDeJardin => 'Terre de jardin',
            self::TerreauSemis => 'Terreau de semis',
            self::TerreauUniversel => 'Terreau universel',
            self::TerreauPlantation => 'Terreau de plantation',
            self::TourbeBlonde => 'Tourbe blonde',
            self::FibreDeCoco => 'Fibre de coco',
            self::Compost => 'Compost',
            self::Fumier => 'Fumier',
            self::Sable => 'Sable',
            self::Perlite => 'Perlite',
            self::Vermiculite => 'Vermiculite',
            self::BillesArgile => "Billes d'argile",
            self::Ecorce => 'Écorce',
            self::Autre => 'Autre',
        };
    }

    /**
     * Composants adaptes au recouvrement direct d'une graine.
     *
     * Sert a signaler une couche de surface discutable au moment du semis,
     * sans jamais l'interdire : c'est une aide, pas une contrainte.
     *
     * @return list<self>
     */
    public static function suitableAsTopLayer(): array
    {
        return [
            self::TerreauSemis,
            self::TerreauUniversel,
            self::FibreDeCoco,
            self::Vermiculite,
            self::Compost,
        ];
    }

    public function isSuitableAsTopLayer(): bool
    {
        return \in_array($this, self::suitableAsTopLayer(), true);
    }
}

<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nature de l'emplacement de culture.
 *
 * Cet enum decrit uniquement le contenant. La protection eventuelle (serre,
 * tunnel, chassis) est portee separement par Shelter : on peut cultiver en
 * pleine terre sous serre comme en bac a l'air libre, et melanger les deux
 * dans un seul enum multiplierait les cas pour rien.
 */
enum PlotType: string
{
    case Jardiniere = 'jardiniere';
    case Pot = 'pot';
    case SacCulture = 'sac_culture';
    case CarreSureleve = 'carre_sureleve';
    case PleineTerre = 'pleine_terre';
    case Butte = 'butte';
    case Lasagne = 'lasagne';

    public function label(): string
    {
        return match ($this) {
            self::Jardiniere => 'Jardinière ou bac',
            self::Pot => 'Pot',
            self::SacCulture => 'Sac de culture',
            self::CarreSureleve => 'Carré potager surélevé',
            self::PleineTerre => 'Pleine terre',
            self::Butte => 'Butte',
            self::Lasagne => 'Lasagne',
        };
    }

    /**
     * Contenant ferme, dont le substrat est entierement rapporte.
     *
     * Seuls ces emplacements ont un volume calculable et une question de
     * drainage : en pleine terre, l'eau s'evacue dans le sol.
     */
    public function isContainer(): bool
    {
        return \in_array($this, [self::Jardiniere, self::Pot, self::SacCulture, self::CarreSureleve], true);
    }

    /**
     * Emplacement en contact avec le sol en place.
     */
    public function isGroundBased(): bool
    {
        return \in_array($this, [self::PleineTerre, self::Butte, self::Lasagne], true);
    }
}

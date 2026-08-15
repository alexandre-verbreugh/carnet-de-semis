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
     * Explication affichee sous le choix, dans le formulaire.
     *
     * Tout le monde ne connait pas « lasagne » ou « butte » : sans ces lignes,
     * la moitie des choix ne veut rien dire pour qui debute.
     */
    public function description(): string
    {
        return match ($this) {
            self::Jardiniere => 'Bac allongé, posé au sol, sur pieds ou en balconnière.',
            self::Pot => 'Contenant individuel, rond ou carré, pour un ou deux plants.',
            self::SacCulture => 'Sac de terreau ouvert ou sac géotextile, posé au sol.',
            self::CarreSureleve => 'Grand bac de 1 m² environ, rempli de substrat rapporté.',
            self::PleineTerre => 'Planche, rang ou parcelle : le sol en place, sans contenant.',
            self::Butte => 'Terre remontée en relief, parfois sur du bois enterré. Permanente, elle draine bien et se réchauffe vite.',
            self::Lasagne => 'Couches alternées de matières brunes (carton, feuilles) et vertes (tontes, épluchures), empilées sans retourner le sol. S\'affaisse beaucoup la première année : mieux vaut y planter que semer.',
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

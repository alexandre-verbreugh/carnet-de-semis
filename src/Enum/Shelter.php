<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Protection au-dessus de l'emplacement.
 *
 * Ce champ n'est pas decoratif : il conditionne la validite des correlations
 * meteo. Rapprocher une levee des 8 mm de pluie tombes cette semaine n'a aucun
 * sens si le semis est sous serre, et les temperatures reelles y sont bien
 * superieures a celles relevees dehors.
 */
enum Shelter: string
{
    case Aucun = 'aucun';
    case Serre = 'serre';
    case Tunnel = 'tunnel';
    case Chassis = 'chassis';
    case Voile = 'voile';
    case Interieur = 'interieur';

    public function label(): string
    {
        return match ($this) {
            self::Aucun => 'Plein air',
            self::Serre => 'Serre',
            self::Tunnel => 'Tunnel',
            self::Chassis => 'Châssis',
            self::Voile => 'Voile de forçage',
            self::Interieur => 'Intérieur',
        };
    }

    /**
     * L'emplacement est-il a l'abri de la pluie ?
     *
     * Un voile de forcage laisse passer l'eau ; une serre non. Sous abri
     * etanche, les precipitations relevees par Open-Meteo ne disent rien de
     * l'arrosage reellement recu.
     */
    public function blocksRain(): bool
    {
        return \in_array($this, [self::Serre, self::Tunnel, self::Chassis, self::Interieur], true);
    }

    /**
     * L'emplacement beneficie-t-il d'un gain de temperature notable ?
     */
    public function warmsUp(): bool
    {
        return self::Aucun !== $this;
    }
}

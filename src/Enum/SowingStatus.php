<?php

declare(strict_types=1);

namespace App\Enum;

enum SowingStatus: string
{
    case Seme = 'seme';
    case Leve = 'leve';
    case EnCroissance = 'en_croissance';
    case EnRecolte = 'en_recolte';
    case Termine = 'termine';
    case Echec = 'echec';

    public function label(): string
    {
        return match ($this) {
            self::Seme => 'Semé',
            self::Leve => 'Levé',
            self::EnCroissance => 'En croissance',
            self::EnRecolte => 'En récolte',
            self::Termine => 'Terminé',
            self::Echec => 'Échec',
        };
    }

    /**
     * Un semis actif est encore suivi : il apparait sur le tableau de bord.
     */
    public function isActive(): bool
    {
        return !\in_array($this, [self::Termine, self::Echec], true);
    }

    /**
     * Statuts pour lesquels une levee est encore attendue.
     */
    public function isAwaitingGermination(): bool
    {
        return self::Seme === $this;
    }
}

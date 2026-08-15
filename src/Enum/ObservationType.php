<?php

declare(strict_types=1);

namespace App\Enum;

enum ObservationType: string
{
    case Semis = 'semis';
    case Levee = 'levee';
    case Arrosage = 'arrosage';
    case Repiquage = 'repiquage';
    case Eclaircissage = 'eclaircissage';
    case Fertilisation = 'fertilisation';
    case Floraison = 'floraison';
    case Fructification = 'fructification';
    case Recolte = 'recolte';
    case Maladie = 'maladie';
    case Ravageur = 'ravageur';
    case Perte = 'perte';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Semis => 'Semis',
            self::Levee => 'Levée',
            self::Arrosage => 'Arrosage',
            self::Repiquage => 'Repiquage',
            self::Eclaircissage => 'Éclaircissage',
            self::Fertilisation => 'Fertilisation',
            self::Floraison => 'Floraison',
            self::Fructification => 'Fructification',
            self::Recolte => 'Récolte',
            self::Maladie => 'Maladie',
            self::Ravageur => 'Ravageur',
            self::Perte => 'Perte',
            self::Note => 'Note',
        };
    }

    /**
     * Icone Tabler associee, utilisee dans la timeline.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Semis => 'ti-seeding',
            self::Levee => 'ti-plant',
            self::Arrosage => 'ti-droplet',
            self::Repiquage => 'ti-arrows-shuffle',
            self::Eclaircissage => 'ti-scissors',
            self::Fertilisation => 'ti-flask',
            self::Floraison => 'ti-flower',
            self::Fructification => 'ti-apple',
            self::Recolte => 'ti-basket',
            self::Maladie => 'ti-virus',
            self::Ravageur => 'ti-bug',
            self::Perte => 'ti-trash',
            self::Note => 'ti-note',
        };
    }

    /**
     * Le nombre de plants leves n'a de sens que pour ces types.
     */
    public function expectsGerminatedCount(): bool
    {
        return \in_array($this, [self::Levee, self::Eclaircissage, self::Perte], true);
    }

    public function expectsHarvestWeight(): bool
    {
        return self::Recolte === $this;
    }

    /**
     * Types traduisant un probleme, mis en avant visuellement.
     */
    public function isProblem(): bool
    {
        return \in_array($this, [self::Maladie, self::Ravageur, self::Perte], true);
    }
}

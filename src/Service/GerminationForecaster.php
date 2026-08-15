<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Sowing;

/**
 * Previsions de levee et de recolte a partir des delais theoriques de l'espece.
 *
 * Toutes les methodes renvoient null quand l'espece n'est pas documentee :
 * afficher une date inventee serait pire que ne rien afficher.
 */
class GerminationForecaster
{
    /**
     * Fenetre de levee attendue.
     *
     * @return array{from: \DateTimeImmutable, to: \DateTimeImmutable}|null
     */
    public function germinationWindow(Sowing $sowing): ?array
    {
        $semeLe = $sowing->getSownAt();
        $espece = $sowing->getSpecies();

        if (null === $semeLe || null === $espece) {
            return null;
        }

        $min = $espece->getGerminationDaysMin();
        $max = $espece->getGerminationDaysMax();

        if (null === $min && null === $max) {
            return null;
        }

        // Une seule borne renseignee suffit : on la prend des deux cotes.
        $min ??= $max;
        $max ??= $min;

        return [
            'from' => $semeLe->modify(\sprintf('+%d days', $min)),
            'to' => $semeLe->modify(\sprintf('+%d days', $max)),
        ];
    }

    /**
     * Date de recolte la plus precoce attendue.
     */
    public function expectedHarvestDate(Sowing $sowing): ?\DateTimeImmutable
    {
        $semeLe = $sowing->getSownAt();
        $jours = $sowing->getSpecies()?->getHarvestDaysMin();

        if (null === $semeLe || null === $jours) {
            return null;
        }

        return $semeLe->modify(\sprintf('+%d days', $jours));
    }

    /**
     * La levee est-elle attendue, mais toujours pas constatee, alors que la
     * fenetre theorique est passee ?
     */
    public function isGerminationOverdue(Sowing $sowing, ?\DateTimeImmutable $today = null): bool
    {
        if (!$sowing->getStatus()->isAwaitingGermination()) {
            return false;
        }

        $fenetre = $this->germinationWindow($sowing);
        if (null === $fenetre) {
            return false;
        }

        return ($today ?? new \DateTimeImmutable('today')) > $fenetre['to'];
    }

    /**
     * Nombre de jours restants avant le debut de la fenetre de levee.
     *
     * Zero si la fenetre est en cours, negatif si elle est depassee.
     */
    public function daysUntilGermination(Sowing $sowing, ?\DateTimeImmutable $today = null): ?int
    {
        $fenetre = $this->germinationWindow($sowing);
        if (null === $fenetre) {
            return null;
        }

        $today ??= new \DateTimeImmutable('today');

        if ($today >= $fenetre['from'] && $today <= $fenetre['to']) {
            return 0;
        }

        $reference = $today < $fenetre['from'] ? $fenetre['from'] : $fenetre['to'];
        $ecart = (int) $today->diff($reference)->days;

        return $today < $fenetre['from'] ? $ecart : -$ecart;
    }

    /**
     * Ecart entre le delai de levee constate et le delai theorique le plus court.
     *
     * Positif : la levee a pris plus de temps que prevu. C'est cet ecart, mis en
     * regard du substrat et de la saison, qui fait progresser d'une annee sur
     * l'autre.
     */
    public function germinationDelayVsTheory(Sowing $sowing): ?int
    {
        $reel = $sowing->getActualGerminationDays();
        $theorique = $sowing->getSpecies()?->getGerminationDaysMin();

        if (null === $reel || null === $theorique) {
            return null;
        }

        return $reel - $theorique;
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Observation;
use App\Entity\Sowing;
use App\Enum\ObservationType;
use App\Enum\SowingStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre une observation et en applique les consequences sur le semis.
 *
 * Le semis porte des valeurs denormalisees (date et nombre de leves, statut)
 * pour eviter de reparcourir tout le journal a chaque affichage. Les tenir a
 * jour est le role de ce service, et de lui seul : c'est ce qui garantit qu'un
 * taux de levee affiche correspond bien a ce qui a ete observe.
 */
class ObservationRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function record(Observation $observation, bool $flush = true): void
    {
        $semis = $observation->getSowing();

        if (null !== $semis) {
            $this->applyToSowing($semis, $observation);
        }

        $this->entityManager->persist($observation);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function applyToSowing(Sowing $semis, Observation $observation): void
    {
        match ($observation->getType()) {
            ObservationType::Levee => $this->applyGermination($semis, $observation),
            ObservationType::Recolte => $this->applyHarvest($semis),
            ObservationType::Perte => $this->applyLoss($semis, $observation),
            ObservationType::Repiquage,
            ObservationType::Eclaircissage,
            ObservationType::Floraison,
            ObservationType::Fructification => $this->advanceToGrowing($semis),
            default => null,
        };
    }

    private function applyGermination(Sowing $semis, Observation $observation): void
    {
        $date = $observation->getObservedAt();

        // Seule la premiere levee fixe la date : une observation ulterieure ne
        // fait que completer le comptage, elle ne redate pas la germination.
        if (null !== $date && (null === $semis->getGerminatedAt() || $date < $semis->getGerminatedAt())) {
            $semis->setGerminatedAt($date);
        }

        $compte = $observation->getGerminatedCount();
        if (null !== $compte) {
            // On retient le maximum observe : les plants leves plus tard
            // s'ajoutent, ils ne remplacent pas ceux deja comptes.
            $semis->setGerminatedCount(max($compte, $semis->getGerminatedCount() ?? 0));
        }

        if (SowingStatus::Seme === $semis->getStatus()) {
            $semis->setStatus(SowingStatus::Leve);
        }
    }

    private function applyHarvest(Sowing $semis): void
    {
        if (\in_array($semis->getStatus(), [SowingStatus::Seme, SowingStatus::Leve, SowingStatus::EnCroissance], true)) {
            $semis->setStatus(SowingStatus::EnRecolte);
        }
    }

    /**
     * Une perte ne solde le semis que si elle est totale.
     *
     * Perdre trois plants sur vingt n'est pas un echec : c'est une information
     * a garder, sans clore le suivi.
     */
    private function applyLoss(Sowing $semis, Observation $observation): void
    {
        $restants = $observation->getGerminatedCount();

        if (0 === $restants) {
            $semis->setStatus(SowingStatus::Echec);
            $semis->setEndedAt($observation->getObservedAt());
            $semis->setGerminatedCount(0);

            if (null === $semis->getFailureReason()) {
                $semis->setFailureReason($observation->getNote());
            }
        }
    }

    private function advanceToGrowing(Sowing $semis): void
    {
        if (\in_array($semis->getStatus(), [SowingStatus::Seme, SowingStatus::Leve], true)) {
            $semis->setStatus(SowingStatus::EnCroissance);
        }
    }
}

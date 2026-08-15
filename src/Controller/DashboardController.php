<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Sowing;
use App\Repository\PlotRepository;
use App\Repository\SowingRepository;
use App\Repository\SpeciesRepository;
use App\Service\GerminationForecaster;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/tableau-de-bord', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        SowingRepository $sowingRepository,
        PlotRepository $plotRepository,
        SpeciesRepository $speciesRepository,
        GerminationForecaster $forecaster,
    ): Response {
        $enCours = $sowingRepository->findActive();
        $enAttente = $sowingRepository->findAwaitingGermination();

        $enRetard = array_values(array_filter(
            $enAttente,
            static fn (Sowing $semis): bool => $forecaster->isGerminationOverdue($semis),
        ));

        $aVenir = array_values(array_filter(
            $enAttente,
            static fn (Sowing $semis): bool => !$forecaster->isGerminationOverdue($semis),
        ));

        return $this->render('dashboard/index.html.twig', [
            'sowings_actifs' => $enCours,
            'en_retard' => $enRetard,
            'a_venir' => $aVenir,
            'taux_global' => $this->globalGerminationRate($sowingRepository->findAllOrdered()),
            'plot_count' => $plotRepository->count([]),
            'species_count' => $speciesRepository->count([]),
            'forecaster' => $forecaster,
        ]);
    }

    /**
     * Taux de levee global, calcule sur les seuls semis exploitables.
     *
     * Un semis dont le nombre de graines n'a pas ete note ne compte ni au
     * numerateur ni au denominateur : l'inclure fausserait la moyenne vers le bas.
     *
     * @param list<Sowing> $sowings
     */
    private function globalGerminationRate(array $sowings): ?float
    {
        $graines = 0;
        $leves = 0;

        foreach ($sowings as $semis) {
            if (null === $semis->getSeedCount() || null === $semis->getGerminatedCount()) {
                continue;
            }

            $graines += $semis->getSeedCount();
            $leves += $semis->getGerminatedCount();
        }

        return $graines > 0 ? $leves / $graines : null;
    }
}

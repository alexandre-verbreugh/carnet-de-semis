<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PlotRepository;
use App\Repository\SowingRepository;
use App\Repository\SpeciesRepository;
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
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'sowing_count' => $sowingRepository->count([]),
            'plot_count' => $plotRepository->count([]),
            'species_count' => $speciesRepository->count([]),
        ]);
    }
}

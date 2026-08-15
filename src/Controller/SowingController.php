<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Sowing;
use App\Entity\Species;
use App\Enum\ObservationType;
use App\Form\SowingForm;
use App\Repository\SowingRepository;
use App\Service\GerminationForecaster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/semis')]
class SowingController extends AbstractController
{
    #[Route('', name: 'app_sowing_index', methods: ['GET'])]
    public function index(Request $request, SowingRepository $sowingRepository, GerminationForecaster $forecaster): Response
    {
        $tousLesSemis = $request->query->getBoolean('tous');

        return $this->render('sowing/index.html.twig', [
            'sowings' => $tousLesSemis ? $sowingRepository->findAllOrdered() : $sowingRepository->findActive(),
            'tous' => $tousLesSemis,
            'forecaster' => $forecaster,
        ]);
    }

    #[Route('/nouveau', name: 'app_sowing_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(mapping: ['espece' => 'id'])] ?Species $espece = null,
    ): Response {
        $semis = new Sowing();

        // Arrive depuis une fiche d'espece : on pre-remplit l'espece et la
        // profondeur conseillee, pour n'avoir plus qu'a valider.
        if (null !== $espece) {
            $semis->setSpecies($espece);
            $semis->setDepthMm($espece->getSowingDepthMm());
        }

        $form = $this->createForm(SowingForm::class, $semis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($semis);

            // Le semis lui-meme ouvre le journal : la timeline commence donc
            // toujours par une entree, sans saisie supplementaire.
            $semis->addObservation($this->buildSowingObservation($semis));

            // Le sachet utilise est decremente d'autant de graines.
            $semis->getSeedLot()?->consumeSeeds($semis->getSeedCount() ?? 0);

            $entityManager->flush();

            $this->addFlash('success', 'Semis enregistré.');

            return $this->redirectToRoute('app_sowing_show', ['id' => $semis->getId()]);
        }

        return $this->render('sowing/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_sowing_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Sowing $semis, GerminationForecaster $forecaster): Response
    {
        return $this->render('sowing/show.html.twig', [
            'semis' => $semis,
            'fenetre' => $forecaster->germinationWindow($semis),
            'recolte_prevue' => $forecaster->expectedHarvestDate($semis),
            'en_retard' => $forecaster->isGerminationOverdue($semis),
            'ecart_theorie' => $forecaster->germinationDelayVsTheory($semis),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_sowing_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Sowing $semis, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SowingForm::class, $semis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Semis mis à jour.');

            return $this->redirectToRoute('app_sowing_show', ['id' => $semis->getId()]);
        }

        return $this->render('sowing/edit.html.twig', [
            'form' => $form,
            'semis' => $semis,
        ]);
    }

    private function buildSowingObservation(Sowing $semis): \App\Entity\Observation
    {
        $observation = new \App\Entity\Observation();
        $observation->setType(ObservationType::Semis);
        $observation->setObservedAt($semis->getSownAt());
        $observation->setPlot($semis->getPlot());
        $observation->setNote($semis->getNotes());

        return $observation;
    }
}

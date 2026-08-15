<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Plot;
use App\Form\PlotForm;
use App\Repository\PlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/emplacements')]
class PlotController extends AbstractController
{
    #[Route('', name: 'app_plot_index', methods: ['GET'])]
    public function index(PlotRepository $plotRepository): Response
    {
        return $this->render('plot/index.html.twig', [
            'plots' => $plotRepository->findBy([], ['isArchived' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'app_plot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $plot = new Plot();
        $form = $this->createForm(PlotForm::class, $plot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($plot);
            $entityManager->flush();

            $this->addFlash('success', \sprintf('Emplacement « %s » créé.', $plot->getName()));

            return $this->redirectToRoute('app_plot_show', ['id' => $plot->getId()]);
        }

        return $this->render('plot/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_plot_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Plot $plot): Response
    {
        return $this->render('plot/show.html.twig', ['plot' => $plot]);
    }

    #[Route('/{id}/modifier', name: 'app_plot_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Plot $plot, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PlotForm::class, $plot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Emplacement mis à jour.');

            return $this->redirectToRoute('app_plot_show', ['id' => $plot->getId()]);
        }

        return $this->render('plot/edit.html.twig', [
            'form' => $form,
            'plot' => $plot,
        ]);
    }
}

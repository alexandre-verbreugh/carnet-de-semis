<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Plot;
use App\Entity\User;
use App\Form\PlotForm;
use App\Repository\PlotRepository;
use App\Security\Voter\OwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/emplacements')]
class PlotController extends AbstractController
{
    #[Route('', name: 'app_plot_index', methods: ['GET'])]
    public function index(PlotRepository $plotRepository, #[CurrentUser] User $user): Response
    {
        return $this->render('plot/index.html.twig', [
            'plots' => $plotRepository->findForOwner($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_plot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $user): Response
    {
        $plot = new Plot();
        $plot->setOwner($user);

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
        $this->denyAccessUnlessGranted(OwnerVoter::VOIR, $plot);

        return $this->render('plot/show.html.twig', ['plot' => $plot]);
    }

    #[Route('/{id}/modifier', name: 'app_plot_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Plot $plot, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(OwnerVoter::MODIFIER, $plot);

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

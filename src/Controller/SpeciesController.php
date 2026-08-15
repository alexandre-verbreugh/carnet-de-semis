<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Species;
use App\Entity\User;
use App\Enum\SpeciesCategory;
use App\Form\SpeciesType;
use App\Repository\SpeciesRepository;
use App\Security\Voter\OwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/especes')]
class SpeciesController extends AbstractController
{
    #[Route('', name: 'app_species_index', methods: ['GET'])]
    public function index(Request $request, SpeciesRepository $speciesRepository, #[CurrentUser] User $user): Response
    {
        $terme = $request->query->getString('q');
        $categorie = SpeciesCategory::tryFrom($request->query->getString('categorie'));

        // « Ce mois-ci » est le filtre le plus utile au jardin : il repond a la
        // seule question qu'on se pose devant un bac vide.
        $moisCourant = $request->query->getBoolean('maintenant')
            ? (int) (new \DateTimeImmutable())->format('n')
            : null;

        return $this->render('species/index.html.twig', [
            'especes' => $speciesRepository->search($user, $terme, $categorie, $moisCourant),
            'terme' => $terme,
            'categorie' => $categorie,
            'maintenant' => null !== $moisCourant,
            'categories' => SpeciesCategory::cases(),
        ]);
    }

    #[Route('/nouvelle', name: 'app_species_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $user): Response
    {
        $espece = new Species();
        // Une fiche saisie ici ne sera jamais ecrasee par app:species:import,
        // et n'encombre pas le catalogue des autres jardiniers.
        $espece->setIsCustom(true);
        $espece->setOwner($user);

        $form = $this->createForm(SpeciesType::class, $espece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($espece);
            $entityManager->flush();

            $this->addFlash('success', \sprintf('« %s » ajoutée au catalogue.', $espece->getFullName()));

            return $this->redirectToRoute('app_species_show', ['id' => $espece->getId()]);
        }

        return $this->render('species/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_species_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Species $espece): Response
    {
        $this->denyAccessUnlessGranted(OwnerVoter::VOIR, $espece);

        return $this->render('species/show.html.twig', ['espece' => $espece]);
    }

    #[Route('/{id}/modifier', name: 'app_species_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Species $espece,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $user,
    ): Response {
        $this->denyAccessUnlessGranted(OwnerVoter::VOIR, $espece);

        $partagee = null === $espece->getOwner();

        // Une fiche du catalogue livre est visible par tout le monde : la
        // modifier en place imposerait la correction d'une personne a toutes
        // les autres. On travaille donc sur une copie personnelle.
        $cible = $partagee ? clone $espece : $espece;

        $form = $this->createForm(SpeciesType::class, $cible);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Une fiche personnelle n'est jamais ecrasee par app:species:import.
            $cible->setIsCustom(true);
            $cible->setOwner($user);

            if ($partagee) {
                $entityManager->persist($cible);
            }

            $entityManager->flush();

            $this->addFlash('success', $partagee
                ? 'Fiche personnelle créée à partir du catalogue.'
                : 'Fiche mise à jour.');

            return $this->redirectToRoute('app_species_show', ['id' => $cible->getId()]);
        }

        return $this->render('species/edit.html.twig', [
            'form' => $form,
            'espece' => $espece,
            'partagee' => $partagee,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\PhotoRepository;
use App\Repository\PlotRepository;
use App\Repository\SowingRepository;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Gestion de son propre compte.
 *
 * La suppression n'est pas une politesse : des lors que l'instance heberge les
 * donnees d'autres personnes, celles-ci doivent pouvoir les recuperer et les
 * effacer sans passer par l'hebergeur.
 */
#[Route('/compte')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $user,
        PlotRepository $plotRepository,
        SowingRepository $sowingRepository,
        PhotoRepository $photoRepository,
    ): Response {
        return $this->render('account/index.html.twig', [
            'plot_count' => $plotRepository->countForOwner($user),
            'sowing_count' => \count($sowingRepository->findAllForOwner($user)),
            'photo_count' => $photoRepository->countForOwner($user),
        ]);
    }

    #[Route('/supprimer', name: 'app_account_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        PhotoRepository $photoRepository,
        PhotoStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('supprimer-compte', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton invalide, suppression annulée.');

            return $this->redirectToRoute('app_account');
        }

        // Le mot de passe est redemande : un compte ouvert sur un telephone
        // pose sur la table ne doit pas pouvoir etre efface par un tiers.
        if (!$passwordHasher->isPasswordValid($user, $request->request->getString('password'))) {
            $this->addFlash('error', 'Mot de passe incorrect, suppression annulée.');

            return $this->redirectToRoute('app_account');
        }

        // Les fichiers sur disque ne sont pas concernes par la suppression en
        // cascade de la base : sans cette boucle, les photos resteraient.
        foreach ($photoRepository->findForOwner($user) as $photo) {
            $storage->remove($photo);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        // La session porte un utilisateur qui n'existe plus : la fermer evite
        // une erreur au prochain chargement de page.
        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Compte et données supprimés.');

        return $this->redirectToRoute('app_home');
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'security.token_storage' => \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class,
        ]);
    }
}

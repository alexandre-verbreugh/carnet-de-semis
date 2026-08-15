<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page de presentation, affichee a la racine aux visiteurs non connectes.
 *
 * Le tableau de bord vit sur /tableau-de-bord : la racine se contente
 * d'aiguiller, selon qu'on est connecte ou non.
 */
class LandingController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(bool:APP_PUBLIC_LANDING)%')]
        private readonly bool $publicLanding,
        #[Autowire('%env(string:APP_REPOSITORY_URL)%')]
        private readonly string $repositoryUrl,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$this->publicLanding) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('landing/index.html.twig', [
            'repository_url' => '' === $this->repositoryUrl ? null : $this->repositoryUrl,
        ]);
    }
}

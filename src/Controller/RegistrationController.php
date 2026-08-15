<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Repository\UserRepository;
use App\Security\HumanCheck;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(bool:APP_OPEN_REGISTRATION)%')]
        private readonly bool $inscriptionsOuvertes,
    ) {
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        HumanCheck $humanCheck,
        LoggerInterface $logger,
    ): Response {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Les inscriptions restent ouvertes tant qu'aucun compte n'existe :
        // c'est ce qui permet d'installer l'application sans acces SSH, meme
        // sur une instance destinee a rester personnelle.
        $premierCompte = 0 === $userRepository->count([]);

        if (!$this->inscriptionsOuvertes && !$premierCompte) {
            throw $this->createNotFoundException('Les inscriptions sont fermées sur cette instance.');
        }

        $form = $this->createForm(RegistrationForm::class, null, [
            'jeton_temps' => $humanCheck->issueToken(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motif = $humanCheck->reject($form);

            if (null !== $motif) {
                // Message volontairement identique a celui d'un identifiant
                // deja pris : rien n'indique au robot ce qui l'a trahi.
                $logger->info('Inscription refusee', ['motif' => $motif]);
                $this->addFlash('error', 'Inscription impossible. Réessaie dans un instant.');

                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                    'premier_compte' => $premierCompte,
                ]);
            }

            /** @var string $identifiant */
            $identifiant = $form->get('username')->getData();

            if (null !== $userRepository->findOneBy(['username' => $identifiant])) {
                $this->addFlash('error', 'Cet identifiant est déjà pris.');

                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                    'premier_compte' => $premierCompte,
                ]);
            }

            /** @var string $motDePasse */
            $motDePasse = $form->get('password')->getData();

            $utilisateur = new User();
            $utilisateur->setUsername($identifiant);
            $utilisateur->setRoles(['ROLE_USER']);
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $motDePasse));

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé. Tu peux te connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
            'premier_compte' => $premierCompte,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Cree un compte, ou remplace le mot de passe d'un compte existant.
 *
 * Il n'y a pas d'inscription en ligne : l'application est mono-utilisateur et
 * destinee a etre exposee publiquement, un formulaire d'inscription serait une
 * porte ouverte.
 */
#[AsCommand(
    name: 'app:user:create',
    description: 'Cree un utilisateur ou change son mot de passe',
)]
class CreateUserCommand extends Command
{
    private const int LONGUEUR_MINIMALE = 12;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Identifiant de connexion')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (demande de maniere masquee si absent)')
            ->addOption('allow-weak', null, InputOption::VALUE_NONE, 'Accepte un mot de passe court : developpement local uniquement');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        if (!\is_string($username) || '' === $username) {
            $username = $io->ask('Identifiant');
        }

        if (!\is_string($username) || '' === trim($username)) {
            $io->error('Identifiant obligatoire.');

            return Command::FAILURE;
        }

        $username = trim($username);

        $password = $input->getOption('password');
        if (!\is_string($password) || '' === $password) {
            $question = new Question('Mot de passe');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $io->askQuestion($question);

            $confirmation = new Question('Confirmation');
            $confirmation->setHidden(true);
            $confirmation->setHiddenFallback(false);

            if ($password !== $io->askQuestion($confirmation)) {
                $io->error('Les deux saisies different.');

                return Command::FAILURE;
            }
        }

        if (!\is_string($password) || '' === $password) {
            $io->error('Mot de passe obligatoire.');

            return Command::FAILURE;
        }

        if (\strlen($password) < self::LONGUEUR_MINIMALE) {
            if (true !== $input->getOption('allow-weak')) {
                $io->error(\sprintf(
                    'Mot de passe trop court : %d caracteres minimum. Utiliser --allow-weak en developpement local.',
                    self::LONGUEUR_MINIMALE,
                ));

                return Command::FAILURE;
            }

            $io->warning('Mot de passe court accepte. A ne jamais utiliser sur une instance accessible depuis internet.');
        }

        $user = $this->userRepository->findOneBy(['username' => $username]);
        $isNew = null === $user;

        if ($isNew) {
            $user = new User();
            $user->setUsername($username);
            $user->setRoles(['ROLE_USER']);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error(\sprintf('%s : %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success($isNew
            ? \sprintf('Compte « %s » cree.', $username)
            : \sprintf('Mot de passe du compte « %s » remplace.', $username));

        return Command::SUCCESS;
    }
}

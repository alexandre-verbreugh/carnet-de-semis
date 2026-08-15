<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Species;
use App\Enum\Exposure;
use App\Enum\SpeciesCategory;
use App\Enum\WaterNeed;
use App\Repository\SpeciesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Importe le catalogue d'especes depuis data/especes.csv.
 *
 * Le catalogue vit dans un CSV plutot que dans du code : corriger un delai de
 * levee ou ajouter une variete ne demande alors aucune connaissance de PHP, ce
 * qui est la condition pour que d'autres jardiniers l'enrichissent.
 *
 * La commande est idempotente et peut donc etre relancee a chaque mise a jour.
 * Les fiches saisies a la main dans l'application (isCustom) ne sont jamais
 * touchees : personne ne doit perdre ses notes en mettant a jour.
 */
#[AsCommand(
    name: 'app:species:import',
    description: 'Importe ou met a jour le catalogue d\'especes depuis data/especes.csv',
)]
class ImportSpeciesCommand extends Command
{
    private const string SEPARATEUR = ';';

    /** Colonnes attendues, dans l'ordre. */
    private const array COLONNES = [
        'nom', 'variete', 'famille', 'categorie', 'profondeur_mm', 'espacement_cm',
        'mois_semis', 'levee_min_j', 'levee_max_j', 'recolte_min_j', 'recolte_max_j',
        'temp_min_germination_c', 'exposition', 'besoin_eau', 'semis_direct', 'notes',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SpeciesRepository $speciesRepository,
        private readonly ValidatorInterface $validator,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du CSV a importer')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse le fichier sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chemin = $input->getOption('file');
        if (!\is_string($chemin) || '' === $chemin) {
            $chemin = $this->projectDir.'/data/especes.csv';
        }

        if (!is_readable($chemin)) {
            $io->error(\sprintf('Fichier illisible : %s', $chemin));

            return Command::FAILURE;
        }

        $handle = fopen($chemin, 'r');
        if (false === $handle) {
            $io->error('Ouverture du fichier impossible.');

            return Command::FAILURE;
        }

        $entete = fgetcsv($handle, 0, self::SEPARATEUR, '"', '\\');
        if (!\is_array($entete)) {
            fclose($handle);
            $io->error('Fichier vide.');

            return Command::FAILURE;
        }

        $entete = array_map(static fn ($valeur): string => trim((string) $valeur), $entete);
        // Retire un eventuel BOM laisse par un tableur.
        if (isset($entete[0])) {
            $entete[0] = preg_replace('/^\x{FEFF}/u', '', $entete[0]) ?? $entete[0];
        }

        if ($entete !== self::COLONNES) {
            fclose($handle);
            $io->error('Colonnes inattendues.');
            $io->listing(['Attendu : '.implode(self::SEPARATEUR, self::COLONNES)]);
            $io->listing(['Trouve  : '.implode(self::SEPARATEUR, $entete)]);

            return Command::FAILURE;
        }

        $dryRun = true === $input->getOption('dry-run');
        $crees = 0;
        $misAJour = 0;
        $ignores = 0;
        $erreurs = [];
        $ligne = 1;

        while (false !== ($donnees = fgetcsv($handle, 0, self::SEPARATEUR, '"', '\\'))) {
            ++$ligne;

            if ([null] === $donnees || [] === array_filter($donnees, static fn ($v): bool => null !== $v && '' !== trim((string) $v))) {
                continue;
            }

            if (\count($donnees) !== \count(self::COLONNES)) {
                $erreurs[] = \sprintf('Ligne %d : %d colonnes au lieu de %d.', $ligne, \count($donnees), \count(self::COLONNES));
                continue;
            }

            /** @var array<string, string> $valeurs */
            $valeurs = array_combine(self::COLONNES, array_map(static fn ($v): string => trim((string) $v), $donnees));

            $nom = $valeurs['nom'];
            $variete = '' === $valeurs['variete'] ? null : $valeurs['variete'];

            if ('' === $nom) {
                $erreurs[] = \sprintf('Ligne %d : nom vide.', $ligne);
                continue;
            }

            $espece = $this->speciesRepository->findOneBy(['name' => $nom, 'variety' => $variete]);

            if (null !== $espece && $espece->isCustom()) {
                ++$ignores;
                continue;
            }

            $nouvelle = null === $espece;
            $espece ??= new Species();

            try {
                $this->remplir($espece, $valeurs);
            } catch (\ValueError $exception) {
                $erreurs[] = \sprintf('Ligne %d (%s) : %s', $ligne, $nom, $exception->getMessage());
                continue;
            }

            $violations = $this->validator->validate($espece);
            if (\count($violations) > 0) {
                foreach ($violations as $violation) {
                    $erreurs[] = \sprintf('Ligne %d (%s) : %s %s', $ligne, $nom, $violation->getPropertyPath(), $violation->getMessage());
                }
                continue;
            }

            if (!$dryRun) {
                $this->entityManager->persist($espece);
            }

            $nouvelle ? ++$crees : ++$misAJour;
        }

        fclose($handle);

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        foreach ($erreurs as $erreur) {
            $io->warning($erreur);
        }

        $io->success(\sprintf(
            '%d creee(s), %d mise(s) a jour, %d fiche(s) personnelle(s) preservee(s)%s',
            $crees,
            $misAJour,
            $ignores,
            $dryRun ? ' — simulation, rien enregistre' : '',
        ));

        return [] === $erreurs ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<string, string> $valeurs
     */
    private function remplir(Species $espece, array $valeurs): void
    {
        $entier = static fn (string $cle): ?int => '' === $valeurs[$cle] ? null : (int) $valeurs[$cle];

        $mois = array_values(array_filter(array_map(
            static fn (string $part): int => (int) trim($part),
            '' === $valeurs['mois_semis'] ? [] : explode(',', $valeurs['mois_semis']),
        ), static fn (int $m): bool => $m >= 1 && $m <= 12));

        $espece
            ->setName($valeurs['nom'])
            ->setVariety('' === $valeurs['variete'] ? null : $valeurs['variete'])
            ->setFamily('' === $valeurs['famille'] ? null : $valeurs['famille'])
            ->setCategory(SpeciesCategory::from($valeurs['categorie']))
            ->setSowingDepthMm($entier('profondeur_mm'))
            ->setSpacingCm($entier('espacement_cm'))
            ->setSowingMonths($mois)
            ->setGerminationDaysMin($entier('levee_min_j'))
            ->setGerminationDaysMax($entier('levee_max_j'))
            ->setHarvestDaysMin($entier('recolte_min_j'))
            ->setHarvestDaysMax($entier('recolte_max_j'))
            ->setGerminationTempMinC($entier('temp_min_germination_c'))
            ->setExposure('' === $valeurs['exposition'] ? null : Exposure::from($valeurs['exposition']))
            ->setWaterNeed('' === $valeurs['besoin_eau'] ? null : WaterNeed::from($valeurs['besoin_eau']))
            ->setDirectSow(\in_array(strtolower($valeurs['semis_direct']), ['oui', '1', 'true', 'yes'], true))
            ->setNotes('' === $valeurs['notes'] ? null : $valeurs['notes'])
            ->setIsCustom(false);
    }
}

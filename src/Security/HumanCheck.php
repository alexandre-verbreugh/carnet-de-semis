<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Form\FormInterface;

/**
 * Deux verifications qui distinguent un visiteur d'un robot, sans rien lui
 * demander et sans appeler de service exterieur.
 *
 * Ce n'est pas une barriere infranchissable, et ce n'est pas presente comme
 * telle : c'est un filtre qui arrete les robots generalistes, ceux qui
 * moissonnent les formulaires sans les avoir jamais regardes.
 */
class HumanCheck
{
    /**
     * Duree en dessous de laquelle un envoi n'a pas pu etre saisi a la main.
     *
     * Trois secondes est volontairement bas : mieux vaut laisser passer un
     * robot lent que refuser quelqu'un qui remplit vite avec un gestionnaire
     * de mots de passe.
     */
    private const int DELAI_MINIMAL_SECONDES = 3;

    /**
     * Au-dela, le formulaire est considere comme perime.
     */
    private const int DELAI_MAXIMAL_SECONDES = 7200;

    public function __construct(private readonly string $secret)
    {
    }

    /**
     * Jeton a placer dans le formulaire, portant l'instant de son affichage.
     */
    public function issueToken(?\DateTimeImmutable $maintenant = null): string
    {
        $horodatage = ($maintenant ?? new \DateTimeImmutable())->getTimestamp();

        return $horodatage.'.'.$this->sign((string) $horodatage);
    }

    /**
     * @return string|null le motif du refus, ou null si l'envoi est plausible
     */
    public function reject(FormInterface $form, ?\DateTimeImmutable $maintenant = null): ?string
    {
        $leurre = $form->has('siteWeb') ? $form->get('siteWeb')->getData() : null;
        if (\is_string($leurre) && '' !== trim($leurre)) {
            return 'champ leurre rempli';
        }

        $jeton = $form->has('affichage') ? $form->get('affichage')->getData() : null;
        if (!\is_string($jeton)) {
            return 'jeton d\'affichage absent';
        }

        $parties = explode('.', $jeton, 2);
        if (2 !== \count($parties) || !hash_equals($this->sign($parties[0]), $parties[1])) {
            return 'jeton d\'affichage falsifie';
        }

        $ecoule = ($maintenant ?? new \DateTimeImmutable())->getTimestamp() - (int) $parties[0];

        if ($ecoule < self::DELAI_MINIMAL_SECONDES) {
            return 'formulaire envoye trop vite';
        }

        if ($ecoule > self::DELAI_MAXIMAL_SECONDES) {
            return 'formulaire expire';
        }

        return null;
    }

    private function sign(string $donnee): string
    {
        return hash_hmac('sha256', $donnee, $this->secret);
    }
}

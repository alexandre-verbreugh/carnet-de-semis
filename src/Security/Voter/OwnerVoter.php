<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Observation;
use App\Entity\Photo;
use App\Entity\Plot;
use App\Entity\SeedLot;
use App\Entity\Sowing;
use App\Entity\Species;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Verifie qu'un enregistrement appartient bien a la personne connectee.
 *
 * Filtrer les listes ne suffit pas : sans ce controle, il suffirait d'ouvrir
 * /semis/42 pour consulter, modifier ou supprimer le semis d'un autre
 * jardinier. C'est la faille la plus courante des applications multi-comptes.
 *
 * @extends Voter<string, object>
 */
class OwnerVoter extends Voter
{
    public const string VOIR = 'VOIR';
    public const string MODIFIER = 'MODIFIER';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VOIR, self::MODIFIER], true)) {
            return false;
        }

        return $subject instanceof Plot
            || $subject instanceof Sowing
            || $subject instanceof Observation
            || $subject instanceof SeedLot
            || $subject instanceof Species
            || $subject instanceof Photo;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof User) {
            return false;
        }

        $proprietaire = $this->resolveOwner($subject);

        // Le catalogue livre n'appartient a personne : tout le monde peut le
        // consulter, personne ne peut le modifier depuis l'application. Une
        // modification en cree une copie personnelle.
        if (null === $proprietaire) {
            return $subject instanceof Species && self::VOIR === $attribute;
        }

        return $proprietaire === $utilisateur;
    }

    private function resolveOwner(object $subject): ?User
    {
        return match (true) {
            $subject instanceof Plot,
            $subject instanceof Sowing,
            $subject instanceof Observation,
            $subject instanceof SeedLot,
            $subject instanceof Species => $subject->getOwner(),
            // Une photo n'a pas de proprietaire propre : elle suit l'observation
            // a laquelle elle est rattachee.
            $subject instanceof Photo => $subject->getObservation()?->getOwner(),
            default => null,
        };
    }
}

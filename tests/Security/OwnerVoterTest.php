<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Observation;
use App\Entity\Photo;
use App\Entity\Plot;
use App\Entity\Sowing;
use App\Entity\Species;
use App\Entity\User;
use App\Security\Voter\OwnerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class OwnerVoterTest extends TestCase
{
    private OwnerVoter $voter;
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        $this->voter = new OwnerVoter($this->createStub(Security::class));

        $this->alice = (new User())->setUsername('alice');
        $this->bob = (new User())->setUsername('bob');
    }

    private function jeton(?User $utilisateur): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($utilisateur);

        return $token;
    }

    private function vote(string $attribut, object $sujet, ?User $utilisateur): int
    {
        return $this->voter->vote($this->jeton($utilisateur), $sujet, [$attribut]);
    }

    public function testUnJardinierAccedeASonEmplacement(): void
    {
        $plot = (new Plot())->setOwner($this->alice);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(OwnerVoter::VOIR, $plot, $this->alice));
    }

    public function testUnJardinierNAccedePasALEmplacementDunAutre(): void
    {
        $plot = (new Plot())->setOwner($this->alice);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(OwnerVoter::VOIR, $plot, $this->bob),
            'Ouvrir /emplacements/42 ne doit jamais montrer le bac de quelqu\'un d\'autre.',
        );
    }

    public function testUnJardinierNeModifiePasLeSemisDunAutre(): void
    {
        $semis = (new Sowing())->setOwner($this->alice);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(OwnerVoter::MODIFIER, $semis, $this->bob));
    }

    public function testUnJardinierNeSupprimePasLObservationDunAutre(): void
    {
        $observation = (new Observation())->setOwner($this->alice);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(OwnerVoter::MODIFIER, $observation, $this->bob));
    }

    public function testUnePhotoSuitLaProprieteDeSonObservation(): void
    {
        $observation = (new Observation())->setOwner($this->alice);
        $photo = (new Photo())->setObservation($observation);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(OwnerVoter::VOIR, $photo, $this->alice));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(OwnerVoter::VOIR, $photo, $this->bob),
            'Une photo de jardin est une donnee privee.',
        );
    }

    public function testLeCatalogueLivreEstVisibleParTous(): void
    {
        $espece = (new Species())->setName('Radis');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(OwnerVoter::VOIR, $espece, $this->alice));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(OwnerVoter::VOIR, $espece, $this->bob));
    }

    public function testLeCatalogueLivreNEstModifiableParPersonne(): void
    {
        $espece = (new Species())->setName('Radis');

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(OwnerVoter::MODIFIER, $espece, $this->alice),
            'Modifier une fiche partagee doit passer par une copie personnelle.',
        );
    }

    public function testUneVarietePersonnelleResteInvisibleAuxAutres(): void
    {
        $espece = (new Species())->setName('Tomate')->setOwner($this->alice);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(OwnerVoter::VOIR, $espece, $this->alice));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(OwnerVoter::VOIR, $espece, $this->bob));
    }

    public function testUnVisiteurAnonymeNAccedeARien(): void
    {
        $plot = (new Plot())->setOwner($this->alice);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(OwnerVoter::VOIR, $plot, null));
    }
}

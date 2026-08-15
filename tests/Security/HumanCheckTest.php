<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\HumanCheck;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class HumanCheckTest extends TestCase
{
    private HumanCheck $check;

    protected function setUp(): void
    {
        $this->check = new HumanCheck('secret-de-test');
    }

    /**
     * Formulaire minimal : seuls le champ leurre et le jeton comptent ici.
     */
    private function formulaire(?string $leurre, ?string $jeton): FormInterface
    {
        $champLeurre = $this->createStub(FormInterface::class);
        $champLeurre->method('getData')->willReturn($leurre);

        $champJeton = $this->createStub(FormInterface::class);
        $champJeton->method('getData')->willReturn($jeton);

        $form = $this->createStub(FormInterface::class);
        $form->method('has')->willReturn(true);
        $form->method('get')->willReturnMap([
            ['siteWeb', $champLeurre],
            ['affichage', $champJeton],
        ]);

        return $form;
    }

    public function testUnEnvoiHumainPasse(): void
    {
        $affichage = new \DateTimeImmutable('2026-08-15 10:00:00');
        $envoi = new \DateTimeImmutable('2026-08-15 10:00:40');

        $form = $this->formulaire(null, $this->check->issueToken($affichage));

        self::assertNull($this->check->reject($form, $envoi));
    }

    public function testLeChampLeurreRempliTrahitUnRobot(): void
    {
        $affichage = new \DateTimeImmutable('2026-08-15 10:00:00');
        $envoi = new \DateTimeImmutable('2026-08-15 10:00:40');

        $form = $this->formulaire('https://spam.example', $this->check->issueToken($affichage));

        self::assertSame('champ leurre rempli', $this->check->reject($form, $envoi));
    }

    public function testUnEnvoiInstantaneEstRefuse(): void
    {
        $affichage = new \DateTimeImmutable('2026-08-15 10:00:00');
        $envoi = new \DateTimeImmutable('2026-08-15 10:00:01');

        $form = $this->formulaire(null, $this->check->issueToken($affichage));

        self::assertSame('formulaire envoye trop vite', $this->check->reject($form, $envoi));
    }

    public function testUnFormulaireOublieDepuisDesHeuresEstRefuse(): void
    {
        $affichage = new \DateTimeImmutable('2026-08-15 10:00:00');
        $envoi = new \DateTimeImmutable('2026-08-15 13:00:00');

        $form = $this->formulaire(null, $this->check->issueToken($affichage));

        self::assertSame('formulaire expire', $this->check->reject($form, $envoi));
    }

    public function testUnJetonForgeEstRefuse(): void
    {
        // Un robot qui devine le format sans connaitre le secret ne peut pas
        // fabriquer la signature.
        $form = $this->formulaire(null, (string) time().'.signaturebidon');

        self::assertSame('jeton d\'affichage falsifie', $this->check->reject($form));
    }

    public function testUnJetonAbsentEstRefuse(): void
    {
        self::assertSame('jeton d\'affichage absent', $this->check->reject($this->formulaire(null, null)));
    }

    public function testUnJetonDuneAutreInstanceEstRefuse(): void
    {
        $autreInstance = new HumanCheck('un-autre-secret');
        $affichage = new \DateTimeImmutable('2026-08-15 10:00:00');
        $envoi = new \DateTimeImmutable('2026-08-15 10:00:40');

        $form = $this->formulaire(null, $autreInstance->issueToken($affichage));

        self::assertSame('jeton d\'affichage falsifie', $this->check->reject($form, $envoi));
    }
}

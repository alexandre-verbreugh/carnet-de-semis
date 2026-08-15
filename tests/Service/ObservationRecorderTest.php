<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Observation;
use App\Entity\Plot;
use App\Entity\Sowing;
use App\Entity\Species;
use App\Enum\ObservationType;
use App\Enum\SowingStatus;
use App\Service\ObservationRecorder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ObservationRecorderTest extends TestCase
{
    private ObservationRecorder $recorder;

    protected function setUp(): void
    {
        // Un stub suffit : ces tests portent sur les effets de bord appliques
        // aux entites, jamais sur les appels a Doctrine, et record() est
        // toujours invoque sans flush.
        $this->recorder = new ObservationRecorder($this->createStub(EntityManagerInterface::class));
    }

    private function semis(int $graines = 24): Sowing
    {
        return (new Sowing())
            ->setPlot(new Plot())
            ->setSpecies((new Species())->setName('Radis'))
            ->setSownAt(new \DateTimeImmutable('2026-08-09'))
            ->setSeedCount($graines);
    }

    private function observation(Sowing $semis, ObservationType $type, string $date): Observation
    {
        $observation = new Observation();
        $observation->setSowing($semis);
        $observation->setType($type);
        $observation->setObservedAt(new \DateTimeImmutable($date));

        return $observation;
    }

    public function testUneLeveeRenseigneLaDateLeComptageEtLeStatut(): void
    {
        $semis = $this->semis();
        $observation = $this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(11);

        $this->recorder->record($observation, false);

        self::assertSame('2026-08-15', $semis->getGerminatedAt()?->format('Y-m-d'));
        self::assertSame(11, $semis->getGerminatedCount());
        self::assertSame(SowingStatus::Leve, $semis->getStatus());
        self::assertEqualsWithDelta(11 / 24, $semis->getGerminationRate(), 0.0001);
    }

    public function testUneSecondeLeveeNeRedatePasLaGermination(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(11), false);
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-18')->setGerminatedCount(16), false);

        self::assertSame('2026-08-15', $semis->getGerminatedAt()?->format('Y-m-d'));
        self::assertSame(16, $semis->getGerminatedCount());
    }

    public function testUnComptageInferieurNeFaitPasReculerLeTotal(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(16), false);
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-18')->setGerminatedCount(9), false);

        self::assertSame(16, $semis->getGerminatedCount());
    }

    public function testUneObservationAnterieureCorrigeLaDate(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(11), false);
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-13')->setGerminatedCount(4), false);

        self::assertSame('2026-08-13', $semis->getGerminatedAt()?->format('Y-m-d'));
    }

    public function testUnePerteTotaleSoldeLeSemis(): void
    {
        $semis = $this->semis();
        $observation = $this->observation($semis, ObservationType::Perte, '2026-08-20')
            ->setGerminatedCount(0)
            ->setNote('Tout grillé pendant la canicule.');

        $this->recorder->record($observation, false);

        self::assertSame(SowingStatus::Echec, $semis->getStatus());
        self::assertSame('2026-08-20', $semis->getEndedAt()?->format('Y-m-d'));
        self::assertSame('Tout grillé pendant la canicule.', $semis->getFailureReason());
    }

    public function testUnePerteRelativeNeSoldePasLeSemis(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(11), false);
        $this->recorder->record($this->observation($semis, ObservationType::Perte, '2026-08-20')->setGerminatedCount(8), false);

        self::assertSame(SowingStatus::Leve, $semis->getStatus());
        self::assertNull($semis->getEndedAt());
    }

    public function testUneRecolteFaitPasserEnRecolte(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Recolte, '2026-09-05')->setHarvestGrams(320), false);

        self::assertSame(SowingStatus::EnRecolte, $semis->getStatus());
    }

    public function testUnArrosageNeChangeRien(): void
    {
        $semis = $this->semis();
        $this->recorder->record($this->observation($semis, ObservationType::Arrosage, '2026-08-12'), false);

        self::assertSame(SowingStatus::Seme, $semis->getStatus());
        self::assertNull($semis->getGerminatedAt());
    }

    public function testTauxDeLeveeInconnuSansNombreDeGraines(): void
    {
        $semis = $this->semis()->setSeedCount(null);
        $this->recorder->record($this->observation($semis, ObservationType::Levee, '2026-08-15')->setGerminatedCount(11), false);

        self::assertNull($semis->getGerminationRate(), 'Un taux calcule sur une donnee manquante serait faux.');
    }

    public function testUneObservationSansSemisNeCassePas(): void
    {
        $observation = new Observation();
        $observation->setPlot(new Plot());
        $observation->setType(ObservationType::Arrosage);
        $observation->setObservedAt(new \DateTimeImmutable('2026-08-12'));

        $this->recorder->record($observation, false);

        self::assertNull($observation->getSowing());
    }
}

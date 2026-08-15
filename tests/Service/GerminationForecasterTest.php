<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Plot;
use App\Entity\Sowing;
use App\Entity\Species;
use App\Enum\SowingStatus;
use App\Service\GerminationForecaster;
use PHPUnit\Framework\TestCase;

class GerminationForecasterTest extends TestCase
{
    private GerminationForecaster $forecaster;

    protected function setUp(): void
    {
        $this->forecaster = new GerminationForecaster();
    }

    private function semis(?int $leveeMin, ?int $leveeMax, string $semeLe = '2026-08-09'): Sowing
    {
        $espece = (new Species())
            ->setName('Radis')
            ->setGerminationDaysMin($leveeMin)
            ->setGerminationDaysMax($leveeMax);

        return (new Sowing())
            ->setPlot(new Plot())
            ->setSpecies($espece)
            ->setSownAt(new \DateTimeImmutable($semeLe));
    }

    public function testFenetreDeLevee(): void
    {
        $fenetre = $this->forecaster->germinationWindow($this->semis(3, 6));

        self::assertNotNull($fenetre);
        self::assertSame('2026-08-12', $fenetre['from']->format('Y-m-d'));
        self::assertSame('2026-08-15', $fenetre['to']->format('Y-m-d'));
    }

    public function testFenetreNulleQuandEspeceNonDocumentee(): void
    {
        self::assertNull($this->forecaster->germinationWindow($this->semis(null, null)));
    }

    public function testUneSeuleBorneSuffit(): void
    {
        $fenetre = $this->forecaster->germinationWindow($this->semis(null, 6));

        self::assertNotNull($fenetre);
        self::assertSame('2026-08-15', $fenetre['from']->format('Y-m-d'));
        self::assertSame('2026-08-15', $fenetre['to']->format('Y-m-d'));
    }

    public function testRetardSeulementApresLaFenetre(): void
    {
        $semis = $this->semis(3, 6);

        self::assertFalse($this->forecaster->isGerminationOverdue($semis, new \DateTimeImmutable('2026-08-15')));
        self::assertTrue($this->forecaster->isGerminationOverdue($semis, new \DateTimeImmutable('2026-08-16')));
    }

    public function testPasDeRetardSiLaLeveeEstDejaConstatee(): void
    {
        $semis = $this->semis(3, 6)->setStatus(SowingStatus::Leve);

        self::assertFalse($this->forecaster->isGerminationOverdue($semis, new \DateTimeImmutable('2026-09-30')));
    }

    public function testJoursRestantsAvantLevee(): void
    {
        $semis = $this->semis(3, 6);

        self::assertSame(3, $this->forecaster->daysUntilGermination($semis, new \DateTimeImmutable('2026-08-09')));
        self::assertSame(0, $this->forecaster->daysUntilGermination($semis, new \DateTimeImmutable('2026-08-13')));
        self::assertSame(-2, $this->forecaster->daysUntilGermination($semis, new \DateTimeImmutable('2026-08-17')));
    }

    public function testEcartAvecLeDelaiTheorique(): void
    {
        $semis = $this->semis(3, 6)->setGerminatedAt(new \DateTimeImmutable('2026-08-15'));

        self::assertSame(3, $this->forecaster->germinationDelayVsTheory($semis));
    }

    public function testEcartNulSansLevee(): void
    {
        self::assertNull($this->forecaster->germinationDelayVsTheory($this->semis(3, 6)));
    }

    public function testRecoltePrevue(): void
    {
        $semis = $this->semis(3, 6);
        $semis->getSpecies()?->setHarvestDaysMin(25);

        self::assertSame('2026-09-03', $this->forecaster->expectedHarvestDate($semis)?->format('Y-m-d'));
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WeatherDayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Releve meteo quotidien, alimente par Open-Meteo.
 *
 * Une ligne par jour, sans lien avec les semis : c'est la date qui fait la
 * jonction au moment de l'affichage et des correlations.
 */
#[ORM\Entity(repositoryClass: WeatherDayRepository::class)]
#[ORM\Table(name: 'weather_day')]
#[ORM\UniqueConstraint(name: 'uniq_weather_day_date', columns: ['date'])]
class WeatherDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $tempMinC = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $tempMaxC = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $precipitationMm = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sunshineHours = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $syncedAt;

    public function __construct()
    {
        $this->syncedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTempMinC(): ?float
    {
        return $this->tempMinC;
    }

    public function setTempMinC(?float $tempMinC): static
    {
        $this->tempMinC = $tempMinC;

        return $this;
    }

    public function getTempMaxC(): ?float
    {
        return $this->tempMaxC;
    }

    public function setTempMaxC(?float $tempMaxC): static
    {
        $this->tempMaxC = $tempMaxC;

        return $this;
    }

    public function getTempMeanC(): ?float
    {
        if (null === $this->tempMinC || null === $this->tempMaxC) {
            return null;
        }

        return round(($this->tempMinC + $this->tempMaxC) / 2, 1);
    }

    public function getPrecipitationMm(): ?float
    {
        return $this->precipitationMm;
    }

    public function setPrecipitationMm(?float $precipitationMm): static
    {
        $this->precipitationMm = $precipitationMm;

        return $this;
    }

    public function getSunshineHours(): ?float
    {
        return $this->sunshineHours;
    }

    public function setSunshineHours(?float $sunshineHours): static
    {
        $this->sunshineHours = $sunshineHours;

        return $this;
    }

    public function getSyncedAt(): \DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function touchSyncedAt(): static
    {
        $this->syncedAt = new \DateTimeImmutable();

        return $this;
    }
}

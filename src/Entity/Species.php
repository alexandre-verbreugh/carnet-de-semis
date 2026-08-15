<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Exposure;
use App\Enum\SpeciesCategory;
use App\Enum\WaterNeed;
use App\Repository\SpeciesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fiche de reference d'une espece ou variete.
 *
 * Les champs germinationDays* et harvestDays* alimentent toutes les previsions
 * (levee attendue, recolte prevue) ; ils sont nullable car certaines varietes
 * ajoutees a la main ne seront pas documentees.
 */
#[ORM\Entity(repositoryClass: SpeciesRepository::class)]
#[ORM\Table(name: 'species')]
#[ORM\UniqueConstraint(name: 'uniq_species_name_variety', columns: ['name', 'variety'])]
class Species
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $variety = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $family = null;

    #[ORM\Column(enumType: SpeciesCategory::class)]
    private SpeciesCategory $category = SpeciesCategory::LegumeFeuille;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 150)]
    private ?int $sowingDepthMm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 200)]
    private ?int $spacingCm = null;

    /**
     * Mois de semis conseilles, de 1 a 12.
     *
     * @var list<int>
     */
    #[ORM\Column(type: 'json')]
    private array $sowingMonths = [];

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 365)]
    private ?int $germinationDaysMin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 365)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'germinationDaysMin')]
    private ?int $germinationDaysMax = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 900)]
    private ?int $harvestDaysMin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 900)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'harvestDaysMin')]
    private ?int $harvestDaysMax = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -10, max: 40)]
    private ?int $germinationTempMinC = null;

    #[ORM\Column(enumType: Exposure::class, nullable: true)]
    private ?Exposure $exposure = null;

    #[ORM\Column(enumType: WaterNeed::class, nullable: true)]
    private ?WaterNeed $waterNeed = null;

    /**
     * L'espece se seme directement en place (par opposition au semis en godet).
     */
    #[ORM\Column]
    private bool $directSow = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /**
     * Vraie pour les fiches saisies a la main, fausse pour celles livrees en fixtures.
     */
    #[ORM\Column]
    private bool $isCustom = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getVariety(): ?string
    {
        return $this->variety;
    }

    public function setVariety(?string $variety): static
    {
        $this->variety = $variety;

        return $this;
    }

    /**
     * Libelle complet, par exemple « Radis — 18 jours ».
     */
    public function getFullName(): string
    {
        return null === $this->variety || '' === $this->variety
            ? $this->name
            : \sprintf('%s — %s', $this->name, $this->variety);
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(?string $family): static
    {
        $this->family = $family;

        return $this;
    }

    public function getCategory(): SpeciesCategory
    {
        return $this->category;
    }

    public function setCategory(SpeciesCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSowingDepthMm(): ?int
    {
        return $this->sowingDepthMm;
    }

    public function setSowingDepthMm(?int $sowingDepthMm): static
    {
        $this->sowingDepthMm = $sowingDepthMm;

        return $this;
    }

    public function getSpacingCm(): ?int
    {
        return $this->spacingCm;
    }

    public function setSpacingCm(?int $spacingCm): static
    {
        $this->spacingCm = $spacingCm;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getSowingMonths(): array
    {
        return $this->sowingMonths;
    }

    /**
     * @param list<int> $sowingMonths
     */
    public function setSowingMonths(array $sowingMonths): static
    {
        $months = array_values(array_unique(array_filter(
            $sowingMonths,
            static fn (int $month): bool => $month >= 1 && $month <= 12,
        )));
        sort($months);
        $this->sowingMonths = $months;

        return $this;
    }

    public function isSowableInMonth(int $month): bool
    {
        return \in_array($month, $this->sowingMonths, true);
    }

    public function getGerminationDaysMin(): ?int
    {
        return $this->germinationDaysMin;
    }

    public function setGerminationDaysMin(?int $germinationDaysMin): static
    {
        $this->germinationDaysMin = $germinationDaysMin;

        return $this;
    }

    public function getGerminationDaysMax(): ?int
    {
        return $this->germinationDaysMax;
    }

    public function setGerminationDaysMax(?int $germinationDaysMax): static
    {
        $this->germinationDaysMax = $germinationDaysMax;

        return $this;
    }

    public function getHarvestDaysMin(): ?int
    {
        return $this->harvestDaysMin;
    }

    public function setHarvestDaysMin(?int $harvestDaysMin): static
    {
        $this->harvestDaysMin = $harvestDaysMin;

        return $this;
    }

    public function getHarvestDaysMax(): ?int
    {
        return $this->harvestDaysMax;
    }

    public function setHarvestDaysMax(?int $harvestDaysMax): static
    {
        $this->harvestDaysMax = $harvestDaysMax;

        return $this;
    }

    public function getGerminationTempMinC(): ?int
    {
        return $this->germinationTempMinC;
    }

    public function setGerminationTempMinC(?int $germinationTempMinC): static
    {
        $this->germinationTempMinC = $germinationTempMinC;

        return $this;
    }

    public function getExposure(): ?Exposure
    {
        return $this->exposure;
    }

    public function setExposure(?Exposure $exposure): static
    {
        $this->exposure = $exposure;

        return $this;
    }

    public function getWaterNeed(): ?WaterNeed
    {
        return $this->waterNeed;
    }

    public function setWaterNeed(?WaterNeed $waterNeed): static
    {
        $this->waterNeed = $waterNeed;

        return $this;
    }

    public function isDirectSow(): bool
    {
        return $this->directSow;
    }

    public function setDirectSow(bool $directSow): static
    {
        $this->directSow = $directSow;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function isCustom(): bool
    {
        return $this->isCustom;
    }

    public function setIsCustom(bool $isCustom): static
    {
        $this->isCustom = $isCustom;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}

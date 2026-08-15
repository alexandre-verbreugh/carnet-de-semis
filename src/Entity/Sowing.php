<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SowingMethod;
use App\Enum\SowingStatus;
use App\Repository\SowingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un semis : une espece, dans une jardiniere, a une date donnee.
 *
 * germinatedAt et germinatedCount sont denormalises depuis la premiere observation
 * de type levee, pour eviter de recalculer l'agregat a chaque affichage du tableau
 * de bord et des statistiques.
 */
#[ORM\Entity(repositoryClass: SowingRepository::class)]
#[ORM\Table(name: 'sowing')]
#[ORM\Index(name: 'idx_sowing_status', columns: ['status'])]
#[ORM\Index(name: 'idx_sowing_sown_at', columns: ['sown_at'])]
class Sowing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Planter::class, inversedBy: 'sowings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Planter $planter = null;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Species $species = null;

    #[ORM\ManyToOne(targetEntity: SeedLot::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SeedLot $seedLot = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $sownAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10000)]
    private ?int $seedCount = null;

    #[ORM\Column(enumType: SowingMethod::class)]
    private SowingMethod $method = SowingMethod::SemisDirect;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 150)]
    private ?int $depthMm = null;

    #[ORM\Column(enumType: SowingStatus::class)]
    private SowingStatus $status = SowingStatus::Seme;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $germinatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 10000)]
    private ?int $germinatedCount = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, Observation>
     */
    #[ORM\OneToMany(targetEntity: Observation::class, mappedBy: 'sowing', cascade: ['remove'])]
    #[ORM\OrderBy(['observedAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $observations;

    public function __construct()
    {
        $this->observations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlanter(): ?Planter
    {
        return $this->planter;
    }

    public function setPlanter(?Planter $planter): static
    {
        $this->planter = $planter;

        return $this;
    }

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function setSpecies(?Species $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getSeedLot(): ?SeedLot
    {
        return $this->seedLot;
    }

    public function setSeedLot(?SeedLot $seedLot): static
    {
        $this->seedLot = $seedLot;

        return $this;
    }

    public function getSownAt(): ?\DateTimeImmutable
    {
        return $this->sownAt;
    }

    public function setSownAt(?\DateTimeImmutable $sownAt): static
    {
        $this->sownAt = $sownAt;

        return $this;
    }

    public function getSeedCount(): ?int
    {
        return $this->seedCount;
    }

    public function setSeedCount(?int $seedCount): static
    {
        $this->seedCount = $seedCount;

        return $this;
    }

    public function getMethod(): SowingMethod
    {
        return $this->method;
    }

    public function setMethod(SowingMethod $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getDepthMm(): ?int
    {
        return $this->depthMm;
    }

    public function setDepthMm(?int $depthMm): static
    {
        $this->depthMm = $depthMm;

        return $this;
    }

    public function getStatus(): SowingStatus
    {
        return $this->status;
    }

    public function setStatus(SowingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGerminatedAt(): ?\DateTimeImmutable
    {
        return $this->germinatedAt;
    }

    public function setGerminatedAt(?\DateTimeImmutable $germinatedAt): static
    {
        $this->germinatedAt = $germinatedAt;

        return $this;
    }

    public function getGerminatedCount(): ?int
    {
        return $this->germinatedCount;
    }

    public function setGerminatedCount(?int $germinatedCount): static
    {
        $this->germinatedCount = $germinatedCount;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;

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

    /**
     * @return Collection<int, Observation>
     */
    public function getObservations(): Collection
    {
        return $this->observations;
    }

    public function addObservation(Observation $observation): static
    {
        if (!$this->observations->contains($observation)) {
            $this->observations->add($observation);
            $observation->setSowing($this);
        }

        return $this;
    }

    public function removeObservation(Observation $observation): static
    {
        if ($this->observations->removeElement($observation) && $observation->getSowing() === $this) {
            $observation->setSowing(null);
        }

        return $this;
    }

    /**
     * Nombre de jours ecoules entre le semis et la levee constatee.
     */
    public function getActualGerminationDays(): ?int
    {
        if (null === $this->sownAt || null === $this->germinatedAt) {
            return null;
        }

        return (int) $this->sownAt->diff($this->germinatedAt)->days;
    }

    /**
     * Taux de levee, entre 0 et 1.
     *
     * Null tant que la levee n'a pas ete constatee ou que le nombre de graines
     * semees est inconnu : un taux calcule sur une donnee manquante serait faux.
     */
    public function getGerminationRate(): ?float
    {
        if (null === $this->seedCount || 0 === $this->seedCount || null === $this->germinatedCount) {
            return null;
        }

        return min(1.0, $this->germinatedCount / $this->seedCount);
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s (%s)',
            $this->species?->getFullName() ?? 'Semis',
            $this->sownAt?->format('d/m/Y') ?? '—',
        );
    }
}

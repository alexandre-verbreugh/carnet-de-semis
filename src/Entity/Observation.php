<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ObservationType;
use App\Repository\ObservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une entree du journal.
 *
 * Rattachee soit a un semis precis, soit a l'emplacement seul (arrosage global,
 * refection du substrat...). L'emplacement est toujours renseigne, y compris
 * quand l'observation porte sur un semis : cela evite une jointure pour toutes
 * les statistiques agregees par emplacement.
 */
#[ORM\Entity(repositoryClass: ObservationRepository::class)]
#[ORM\Table(name: 'observation')]
#[ORM\Index(name: 'idx_observation_observed_at', columns: ['observed_at'])]
#[ORM\Index(name: 'idx_observation_type', columns: ['type'])]
class Observation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sowing::class, inversedBy: 'observations')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Sowing $sowing = null;

    #[ORM\ManyToOne(targetEntity: Plot::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Plot $plot = null;

    /**
     * Proprietaire des donnees.
     *
     * L'instance pouvant heberger plusieurs jardiniers, chaque enregistrement
     * doit savoir a qui il appartient : sans ce champ, tout le monde verrait
     * les semis de tout le monde.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $observedAt = null;

    #[ORM\Column(enumType: ObservationType::class)]
    private ObservationType $type = ObservationType::Note;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 1000)]
    private ?int $heightCm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 1000)]
    private ?int $leafCount = null;

    /**
     * Nombre de plants leves constate ce jour-la.
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 10000)]
    private ?int $germinatedCount = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 1000000)]
    private ?int $harvestGrams = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'observation', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSowing(): ?Sowing
    {
        return $this->sowing;
    }

    public function setSowing(?Sowing $sowing): static
    {
        $this->sowing = $sowing;

        if (null !== $sowing && null === $this->plot) {
            $this->plot = $sowing->getPlot();
        }

        return $this;
    }

    public function getPlot(): ?Plot
    {
        return $this->plot;
    }

    public function setPlot(?Plot $plot): static
    {
        $this->plot = $plot;

        return $this;
    }

    public function getObservedAt(): ?\DateTimeImmutable
    {
        return $this->observedAt;
    }

    public function setObservedAt(?\DateTimeImmutable $observedAt): static
    {
        $this->observedAt = $observedAt;

        return $this;
    }

    public function getType(): ObservationType
    {
        return $this->type;
    }

    public function setType(ObservationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getHeightCm(): ?int
    {
        return $this->heightCm;
    }

    public function setHeightCm(?int $heightCm): static
    {
        $this->heightCm = $heightCm;

        return $this;
    }

    public function getLeafCount(): ?int
    {
        return $this->leafCount;
    }

    public function setLeafCount(?int $leafCount): static
    {
        $this->leafCount = $leafCount;

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

    public function getHarvestGrams(): ?int
    {
        return $this->harvestGrams;
    }

    public function setHarvestGrams(?int $harvestGrams): static
    {
        $this->harvestGrams = $harvestGrams;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setObservation($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo) && $photo->getObservation() === $this) {
            $photo->setObservation(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s — %s',
            $this->observedAt?->format('d/m/Y') ?? '—',
            $this->type->label(),
        );
    }
}

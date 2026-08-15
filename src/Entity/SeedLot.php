<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SeedLotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un sachet de graines.
 *
 * Le stock restant est decremente au moment du semis, ce qui permet de savoir
 * ce qu'il reste avant la saison suivante et de reperer les sachets perimes.
 */
#[ORM\Entity(repositoryClass: SeedLotRepository::class)]
#[ORM\Table(name: 'seed_lot')]
class SeedLot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Species::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Species $species = null;

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

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $lotRef = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $purchasedAt = null;

    /**
     * Date limite d'utilisation conseillee indiquee sur le sachet.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100000)]
    private ?int $initialSeedCount = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100000)]
    private ?int $remainingSeedCount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

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

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function setSpecies(?Species $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getLotRef(): ?string
    {
        return $this->lotRef;
    }

    public function setLotRef(?string $lotRef): static
    {
        $this->lotRef = $lotRef;

        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(?\DateTimeImmutable $purchasedAt): static
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(?\DateTimeImmutable $reference = null): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < ($reference ?? new \DateTimeImmutable('today'));
    }

    public function getInitialSeedCount(): ?int
    {
        return $this->initialSeedCount;
    }

    public function setInitialSeedCount(?int $initialSeedCount): static
    {
        $this->initialSeedCount = $initialSeedCount;

        return $this;
    }

    public function getRemainingSeedCount(): ?int
    {
        return $this->remainingSeedCount;
    }

    public function setRemainingSeedCount(?int $remainingSeedCount): static
    {
        $this->remainingSeedCount = null === $remainingSeedCount ? null : max(0, $remainingSeedCount);

        return $this;
    }

    /**
     * Retire des graines du stock, sans jamais passer sous zero.
     *
     * Un stock inconnu (null) le reste : mieux vaut pas de chiffre qu'un chiffre faux.
     */
    public function consumeSeeds(int $count): static
    {
        if (null === $this->remainingSeedCount || $count <= 0) {
            return $this;
        }

        $this->remainingSeedCount = max(0, $this->remainingSeedCount - $count);

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

    public function __toString(): string
    {
        $parts = array_filter([
            $this->species?->getFullName(),
            $this->brand,
            $this->lotRef,
        ]);

        return implode(' · ', $parts);
    }
}

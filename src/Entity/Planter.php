<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Exposure;
use App\Enum\SubstrateComponent;
use App\Repository\PlanterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une jardiniere, un bac ou un pot.
 *
 * Le substrat est decrit par deux champs complementaires :
 *  - substrateComponents : tout ce qui compose le remplissage (un bac est presque
 *    toujours un melange) ;
 *  - topLayer : ce qui recouvre effectivement la graine. C'est ce dernier qui
 *    determine la levee, et donc la seule base de comparaison honnete entre bacs.
 */
#[ORM\Entity(repositoryClass: PlanterRepository::class)]
#[ORM\Table(name: 'planter')]
class Planter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 1000)]
    private ?int $lengthCm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 1000)]
    private ?int $widthCm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 200)]
    private ?int $depthCm = null;

    /**
     * Composition du remplissage.
     *
     * @var list<string> valeurs de SubstrateComponent
     */
    #[ORM\Column(type: 'json')]
    private array $substrateComponents = [];

    #[ORM\Column(enumType: SubstrateComponent::class, nullable: true)]
    private ?SubstrateComponent $topLayer = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $substrateNote = null;

    #[ORM\Column(enumType: Exposure::class, nullable: true)]
    private ?Exposure $exposure = null;

    #[ORM\Column]
    private bool $hasDrainage = false;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $filledAt = null;

    /**
     * Une jardiniere retiree du suivi reste consultable mais n'accepte plus de semis.
     */
    #[ORM\Column]
    private bool $isArchived = false;

    /**
     * @var Collection<int, Sowing>
     */
    #[ORM\OneToMany(targetEntity: Sowing::class, mappedBy: 'planter')]
    private Collection $sowings;

    public function __construct()
    {
        $this->sowings = new ArrayCollection();
    }

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getLengthCm(): ?int
    {
        return $this->lengthCm;
    }

    public function setLengthCm(?int $lengthCm): static
    {
        $this->lengthCm = $lengthCm;

        return $this;
    }

    public function getWidthCm(): ?int
    {
        return $this->widthCm;
    }

    public function setWidthCm(?int $widthCm): static
    {
        $this->widthCm = $widthCm;

        return $this;
    }

    public function getDepthCm(): ?int
    {
        return $this->depthCm;
    }

    public function setDepthCm(?int $depthCm): static
    {
        $this->depthCm = $depthCm;

        return $this;
    }

    /**
     * Volume utile en litres, calcule a partir des dimensions.
     */
    public function getVolumeL(): ?float
    {
        if (null === $this->lengthCm || null === $this->widthCm || null === $this->depthCm) {
            return null;
        }

        return round($this->lengthCm * $this->widthCm * $this->depthCm / 1000, 1);
    }

    /**
     * @return list<SubstrateComponent>
     */
    public function getSubstrateComponents(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $value): ?SubstrateComponent => SubstrateComponent::tryFrom($value),
            $this->substrateComponents,
        )));
    }

    /**
     * @param list<SubstrateComponent> $components
     */
    public function setSubstrateComponents(array $components): static
    {
        $this->substrateComponents = array_values(array_unique(array_map(
            static fn (SubstrateComponent $component): string => $component->value,
            $components,
        )));

        return $this;
    }

    public function hasSubstrateComponent(SubstrateComponent $component): bool
    {
        return \in_array($component->value, $this->substrateComponents, true);
    }

    public function getTopLayer(): ?SubstrateComponent
    {
        return $this->topLayer;
    }

    public function setTopLayer(?SubstrateComponent $topLayer): static
    {
        $this->topLayer = $topLayer;

        return $this;
    }

    public function getSubstrateNote(): ?string
    {
        return $this->substrateNote;
    }

    public function setSubstrateNote(?string $substrateNote): static
    {
        $this->substrateNote = $substrateNote;

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

    public function hasDrainage(): bool
    {
        return $this->hasDrainage;
    }

    public function setHasDrainage(bool $hasDrainage): static
    {
        $this->hasDrainage = $hasDrainage;

        return $this;
    }

    public function getFilledAt(): ?\DateTimeImmutable
    {
        return $this->filledAt;
    }

    public function setFilledAt(?\DateTimeImmutable $filledAt): static
    {
        $this->filledAt = $filledAt;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * @return Collection<int, Sowing>
     */
    public function getSowings(): Collection
    {
        return $this->sowings;
    }

    public function addSowing(Sowing $sowing): static
    {
        if (!$this->sowings->contains($sowing)) {
            $this->sowings->add($sowing);
            $sowing->setPlanter($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

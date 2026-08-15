<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Exposure;
use App\Enum\PlotType;
use App\Enum\Shelter;
use App\Enum\SubstrateComponent;
use App\Repository\PlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un emplacement de culture : jardiniere, pot, carre surel3eve, planche de
 * pleine terre, butte...
 *
 * Deux dimensions independantes le decrivent : le contenant (type) et la
 * protection (shelter). Un semis peut se faire en pleine terre sous serre
 * comme en bac a l'air libre.
 *
 * Le substrat est lui aussi decrit par deux champs :
 *  - substrateComponents : tout ce qui compose le remplissage, un substrat
 *    etant presque toujours un melange ;
 *  - topLayer : ce qui recouvre effectivement la graine. C'est ce dernier qui
 *    determine la levee, et donc la seule base de comparaison honnete entre
 *    emplacements.
 */
#[ORM\Entity(repositoryClass: PlotRepository::class)]
#[ORM\Table(name: 'plot')]
class Plot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(enumType: PlotType::class)]
    private PlotType $type = PlotType::Jardiniere;

    #[ORM\Column(enumType: Shelter::class)]
    private Shelter $shelter = Shelter::Aucun;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10000)]
    private ?int $lengthCm = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10000)]
    private ?int $widthCm = null;

    /**
     * Profondeur utile. N'a de sens que pour un contenant : en pleine terre,
     * c'est le sol qui decide.
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 200)]
    private ?int $depthCm = null;

    /**
     * Composition du remplissage ou du sol.
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
     * Un emplacement archive reste consultable mais n'accepte plus de semis.
     */
    #[ORM\Column]
    private bool $isArchived = false;

    /**
     * @var Collection<int, Sowing>
     */
    #[ORM\OneToMany(targetEntity: Sowing::class, mappedBy: 'plot')]
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

    public function getType(): PlotType
    {
        return $this->type;
    }

    public function setType(PlotType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getShelter(): Shelter
    {
        return $this->shelter;
    }

    public function setShelter(Shelter $shelter): static
    {
        $this->shelter = $shelter;

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
     * Surface cultivee en metres carres.
     */
    public function getAreaM2(): ?float
    {
        if (null === $this->lengthCm || null === $this->widthCm) {
            return null;
        }

        return round($this->lengthCm * $this->widthCm / 10000, 2);
    }

    /**
     * Volume de substrat en litres.
     *
     * Null hors contenant : parler du volume d'une planche de pleine terre
     * n'aurait aucun sens.
     */
    public function getVolumeL(): ?float
    {
        if (!$this->type->isContainer()) {
            return null;
        }

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

    /**
     * La question du drainage ne se pose que pour un contenant.
     */
    public function isDrainageRelevant(): bool
    {
        return $this->type->isContainer();
    }

    /**
     * Les releves de pluie decrivent-ils ce que cet emplacement a recu ?
     */
    public function receivesRain(): bool
    {
        return !$this->shelter->blocksRain();
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
            $sowing->setPlot($this);
        }

        return $this;
    }

    /**
     * Resume affichable : « Jardinière ou bac · sous serre ».
     */
    public function getShortDescription(): string
    {
        return Shelter::Aucun === $this->shelter
            ? $this->type->label()
            : \sprintf('%s · %s', $this->type->label(), $this->shelter->label());
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

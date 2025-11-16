<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\RideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RideRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['ride:read']],
    denormalizationContext: ['groups' => ['ride:write']],
    processor: \App\State\RideProcessor::class,
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(
            security: "is_granted('ROLE_USER') and (object.getDriver() == user or object.getPassenger() == user)",
            denormalizationContext: ['groups' => ['ride:update']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Opération personnalisée : accepter une course
        new Post(
            uriTemplate: '/rides/{id}/accept',
            security: "is_granted('ROLE_USER')",
            processor: \App\State\RideAcceptProcessor::class,
            denormalizationContext: ['groups' => ['ride:accept']],
            description: 'Accept a ride as a driver'
        ),
        // Opération personnalisée : mettre à jour le statut
        new Patch(
            uriTemplate: '/rides/{id}/status',
            security: "is_granted('ROLE_USER') and object.getDriver() == user",
            processor: \App\State\RideStatusProcessor::class,
            denormalizationContext: ['groups' => ['ride:status']],
            description: 'Update ride status (driver only)'
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'vehiculeType' => 'exact',
    'passenger' => 'exact',
    'driver' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['estimatedPrice', 'finalPrice', 'estimatedDistance'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'acceptedAt', 'completedAt', 'estimatedPrice'])]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ride:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ridesAsDriver')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['ride:read'])]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'ridesAsPassenger')]
    #[Groups(['ride:read', 'ride:write'])]
    private ?User $passenger = null;

    #[ORM\Column(length: 20)]
    #[Groups(['ride:read', 'ride:write', 'ride:status'])]
    #[Assert\Choice(choices: ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?string $pickUpAddress = null;

    #[ORM\Column]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?float $pickUpLatitude = null;

    #[ORM\Column]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?float $pickUpLongitude = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?string $dropoffAddress = null;

    #[ORM\Column]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?float $dropoffLatitude = null;

    #[ORM\Column]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\NotBlank]
    private ?float $dropoffLongitude = null;

    #[ORM\Column]
    #[Groups(['ride:read'])]
    private ?float $estimatedDistance = null;

    #[ORM\Column]
    #[Groups(['ride:read'])]
    private ?float $estimatedPrice = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read'])]
    private ?float $estimatedDuration = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read'])]
    private ?float $finalPrice = null;

    #[ORM\Column(length: 20)]
    #[Groups(['ride:read', 'ride:write'])]
    #[Assert\Choice(choices: ['standard', 'comfort', 'premium', 'xl'])]
    private ?string $vehiculeType = null;

    #[ORM\Column]
    #[Groups(['ride:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read'])]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'ride')]
    private Collection $ratings;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->ratings = new ArrayCollection();
    }


    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): static
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPickUpAddress(): ?string
    {
        return $this->pickUpAddress;
    }

    public function setPickUpAddress(string $pickUpAddress): static
    {
        $this->pickUpAddress = $pickUpAddress;

        return $this;
    }

    public function getPickUpLatitude(): ?float
    {
        return $this->pickUpLatitude;
    }

    public function setPickUpLatitude(float $pickUpLatitude): static
    {
        $this->pickUpLatitude = $pickUpLatitude;

        return $this;
    }

    public function getPickUpLongitude(): ?float
    {
        return $this->pickUpLongitude;
    }

    public function setPickUpLongitude(float $pickUpLongitude): static
    {
        $this->pickUpLongitude = $pickUpLongitude;

        return $this;
    }

    public function getDropoffAddress(): ?string
    {
        return $this->dropoffAddress;
    }

    public function setDropoffAddress(string $dropoffAddress): static
    {
        $this->dropoffAddress = $dropoffAddress;

        return $this;
    }

    public function getDropoffLatitude(): ?float
    {
        return $this->dropoffLatitude;
    }

    public function setDropoffLatitude(float $dropoffLatitude): static
    {
        $this->dropoffLatitude = $dropoffLatitude;

        return $this;
    }

    public function getDropoffLongitude(): ?float
    {
        return $this->dropoffLongitude;
    }

    public function setDropoffLongitude(float $dropoffLongitude): static
    {
        $this->dropoffLongitude = $dropoffLongitude;

        return $this;
    }

    public function getEstimatedDistance(): ?float
    {
        return $this->estimatedDistance;
    }

    public function setEstimatedDistance(float $estimatedDistance): static
    {
        $this->estimatedDistance = $estimatedDistance;

        return $this;
    }

    public function getEstimatedPrice(): ?float
    {
        return $this->estimatedPrice;
    }

    public function setEstimatedPrice(float $estimatedPrice): static
    {
        $this->estimatedPrice = $estimatedPrice;

        return $this;
    }

    public function getEstimatedDuration(): ?float
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?float $estimatedDuration): static
    {
        $this->estimatedDuration = $estimatedDuration;

        return $this;
    }

    public function getFinalPrice(): ?float
    {
        return $this->finalPrice;
    }

    public function setFinalPrice(?float $finalPrice): static
    {
        $this->finalPrice = $finalPrice;

        return $this;
    }

    public function getVehiculeType(): ?string
    {
        return $this->vehiculeType;
    }

    public function setVehiculeType(string $vehiculeType): static
    {
        $this->vehiculeType = $vehiculeType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setRide($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getRide() === $this) {
                $rating->setRide(null);
            }
        }

        return $this;
    }
}

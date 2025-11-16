<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\DriverRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DriverRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['driver:read']],
    denormalizationContext: ['groups' => ['driver:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_USER') and object.getUser() == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Opération personnalisée : mettre à jour la localisation
        new Patch(
            uriTemplate: '/drivers/location',
            security: "is_granted('ROLE_USER')",
            processor: \App\State\DriverLocationProcessor::class,
            denormalizationContext: ['groups' => ['driver:location']],
            read: false,
            description: 'Update driver location'
        ),
        // Opération personnalisée : mettre à jour la disponibilité
        new Patch(
            uriTemplate: '/drivers/availability',
            security: "is_granted('ROLE_USER')",
            processor: \App\State\DriverAvailabilityProcessor::class,
            denormalizationContext: ['groups' => ['driver:availability']],
            read: false,
            description: 'Toggle driver availability'
        )
    ]
)]
#[ApiFilter(BooleanFilter::class, properties: ['isAvailable', 'isVerified'])]
#[ApiFilter(SearchFilter::class, properties: [
    'vehiculeType' => 'exact',
    'vehiculeColor' => 'partial',
    'vehiculeModel' => 'partial'
])]
#[ApiFilter(OrderFilter::class, properties: ['verifiedAt'])]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['driver:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'driver', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['driver:read', 'driver:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Groups(['driver:read', 'driver:write'])]
    #[Assert\NotBlank]
    private ?string $vehiculeModel = null;

    #[ORM\Column(length: 50)]
    #[Groups(['driver:read', 'driver:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['standard', 'comfort', 'premium', 'xl'])]
    private ?string $vehiculeType = null;

    #[ORM\Column(length: 50)]
    #[Groups(['driver:read', 'driver:write'])]
    #[Assert\NotBlank]
    private ?string $vehiculeColor = null;

    #[ORM\Column]
    #[Groups(['driver:read', 'driver:write', 'driver:location'])]
    private ?float $currentLatitude = null;

    #[ORM\Column]
    #[Groups(['driver:read', 'driver:write', 'driver:location'])]
    private ?float $currentLongitude = null;

    #[ORM\Column(length: 50)]
    #[Groups(['driver:read', 'driver:write'])]
    #[Assert\NotBlank]
    private ?string $licenceNumber = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['driver:read'])]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column]
    #[Groups(['driver:read'])]
    private bool $isVerified = false;

    #[ORM\Column]
    #[Groups(['driver:read', 'driver:write', 'driver:availability'])]
    private bool $isAvailable = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getVehiculeModel(): ?string
    {
        return $this->vehiculeModel;
    }

    public function setVehiculeModel(string $vehiculeModel): static
    {
        $this->vehiculeModel = $vehiculeModel;

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

    public function getVehiculeColor(): ?string
    {
        return $this->vehiculeColor;
    }

    public function setVehiculeColor(string $vehiculeColor): static
    {
        $this->vehiculeColor = $vehiculeColor;

        return $this;
    }

    public function getCurrentLatitude(): ?float
    {
        return $this->currentLatitude;
    }

    public function setCurrentLatitude(float $currentLatitude): static
    {
        $this->currentLatitude = $currentLatitude;

        return $this;
    }

    public function getCurrentLongitude(): ?float
    {
        return $this->currentLongitude;
    }

    public function setCurrentLongitude(float $currentLongitude): static
    {
        $this->currentLongitude = $currentLongitude;

        return $this;
    }

    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(string $licenceNumber): static
    {
        $this->licenceNumber = $licenceNumber;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }
}

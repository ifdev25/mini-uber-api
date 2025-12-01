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
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['user:write']],
    processor: \App\State\UserPasswordHashProcessor::class,
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(security: "is_granted('ROLE_USER') and object == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'userType' => 'exact',
    'email' => 'partial',
    'firstName' => 'partial',
    'lastName' => 'partial'
])]
#[ApiFilter(RangeFilter::class, properties: ['rating', 'totalRides'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'rating', 'totalRides'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[Groups(['user:read', 'driver:read', 'ride:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'email', type: 'string', length: 180)]
    #[Groups(['user:read', 'user:write', 'driver:read', 'ride:read', 'rating:read'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(name: 'roles', type: Types::ARRAY)]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(groups: ['user:write'])]
    #[Assert\Length(min: 6)]
    private ?string $password = null;

    #[ORM\Column(name: 'firstname', type: 'string', length: 255)]
    #[Groups(['user:read', 'user:write', 'driver:read', 'ride:read'])]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(name: 'lastname', type: 'string', length: 255)]
    #[Groups(['user:read', 'user:write', 'driver:read', 'ride:read'])]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 255)]
    #[Groups(['user:read', 'user:write', 'rating:read'])]
    #[Assert\NotBlank]
    private ?string $phone = null;

    #[ORM\Column(name: 'usertype', type: 'string', length: 20)]
    #[Groups(['user:read', 'user:write', 'rating:read'])]
    #[Assert\Choice(choices: ['passenger', 'driver'])]
    private ?string $userType = null;

    #[ORM\Column(name: 'rating', type: 'float', nullable: true)]
    #[Groups(['user:read', 'driver:read', 'ride:read'])]
    private ?float $rating = null;

    #[ORM\Column(name: 'totalrides', type: 'integer', nullable: true)]
    #[Groups(['user:read', 'rating:read'])]
    private ?int $totalRides = null;

    #[ORM\Column(name: 'profilepicture', type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write', 'rating:read'])]
    private ?string $profilePicture = null;

    #[ORM\Column(name: 'createdat', type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'isverified', type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['user:read'])]
    private bool $isVerified = false;

    #[ORM\Column(name: 'verificationtoken', type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(name: 'verificationtokenexpiresat', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpiresAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    #[MaxDepth(1)]
    private ?Driver $driver = null;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'driver', orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $ridesAsDriver;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'passenger')]
    #[Groups(['user:read'])]
    private Collection $ridesAsPassenger;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'rater')]
    private Collection $ratingsGiven;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'rated')]
    private Collection $ratingsReceived;

    public function __construct()
    {
        $this->ridesAsDriver = new ArrayCollection();
        $this->ridesAsPassenger = new ArrayCollection();
        $this->ratingsGiven = new ArrayCollection();
        $this->ratingsReceived = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(string $userType): static
    {
        $this->userType = $userType;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTotalRides(): ?int
    {
        return $this->totalRides;
    }

    public function setTotalRides(?int $totalRides): static
    {
        $this->totalRides = $totalRides;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable 
    { 
        return $this->createdAt; 
    }

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(Driver $driver): static
    {
        if ($driver->getUser() !== $this) {
            $driver->setUser($this);
        }

        $this->driver = $driver;

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesAsDriver(): Collection
    {
        return $this->ridesAsDriver;
    }

    public function addRidesAsDriver(Ride $ride): static
    {
        if (!$this->ridesAsDriver->contains($ride)) {
            $this->ridesAsDriver->add($ride);
            $ride->setDriver($this);
        }

        return $this;
    }

    public function removeRidesAsDriver(Ride $ride): static
    {
        if ($this->ridesAsDriver->removeElement($ride)) {
            if ($ride->getDriver() === $this) {
                $ride->setDriver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesAsPassenger(): Collection
    {
        return $this->ridesAsPassenger;
    }

    public function addRidesAsPassenger(Ride $ride): static
    {
        if (!$this->ridesAsPassenger->contains($ride)) {
            $this->ridesAsPassenger->add($ride);
            $ride->setPassenger($this);
        }

        return $this;
    }

    public function removeRidesAsPassenger(Ride $ride): static
    {
        if ($this->ridesAsPassenger->removeElement($ride)) {
            if ($ride->getPassenger() === $this) {
                $ride->setPassenger(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatingsGiven(): Collection { return $this->ratingsGiven;}

    public function addRatingGiven(Rating $rating): static
    {
        if (!$this->ratingsGiven->contains($rating)) {
            $this->ratingsGiven->add($rating);
            $rating->setRater($this);
        }

        return $this;
    }

    public function removeRatingGiven(Rating $rating): static
    {
        if ($this->ratingsGiven->removeElement($rating)) {
            if ($rating->getRater() === $this) {
                $rating->setRater(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatingsReceived(): Collection { return $this->ratingsReceived;}

    public function addRatingReceived(Rating $rating): static
    {
        if (!$this->ratingsReceived->contains($rating)) {
            $this->ratingsReceived->add($rating);
            $rating->setRated($this);
        }

        return $this;
    }

    public function removeRatingReceived(Rating $rating): static
    {
        if ($this->ratingsReceived->removeElement($rating)) {
            if ($rating->getRated() === $this) {
                $rating->setRated(null);
            }
        }

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

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTimeImmutable $verificationTokenExpiresAt): static
    {
        $this->verificationTokenExpiresAt = $verificationTokenExpiresAt;
        return $this;
    }

    // UserInterface methods
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function addRatingsGiven(Rating $ratingsGiven): static
    {
        if (!$this->ratingsGiven->contains($ratingsGiven)) {
            $this->ratingsGiven->add($ratingsGiven);
            $ratingsGiven->setRater($this);
        }

        return $this;
    }

    public function removeRatingsGiven(Rating $ratingsGiven): static
    {
        if ($this->ratingsGiven->removeElement($ratingsGiven)) {
            // set the owning side to null (unless already changed)
            if ($ratingsGiven->getRater() === $this) {
                $ratingsGiven->setRater(null);
            }
        }

        return $this;
    }

    public function addRatingsReceived(Rating $ratingsReceived): static
    {
        if (!$this->ratingsReceived->contains($ratingsReceived)) {
            $this->ratingsReceived->add($ratingsReceived);
            $ratingsReceived->setRated($this);
        }

        return $this;
    }

    public function removeRatingsReceived(Rating $ratingsReceived): static
    {
        if ($this->ratingsReceived->removeElement($ratingsReceived)) {
            // set the owning side to null (unless already changed)
            if ($ratingsReceived->getRated() === $this) {
                $ratingsReceived->setRated(null);
            }
        }

        return $this;
    }
}

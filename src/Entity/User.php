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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
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
    'usertype' => 'exact',
    'email' => 'partial',
    'firstname' => 'partial',
    'lastname' => 'partial'
])]
#[ApiFilter(RangeFilter::class, properties: ['rating', 'totalRides'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'rating', 'totalRides'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: Types::ARRAY)]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    #[Groups(['user:write'])]
    #[Assert\NotBlank(groups: ['user:write'])]
    #[Assert\Length(min: 6)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank]
    private ?string $phone = null;

    #[ORM\Column(length: 20)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\Choice(choices: ['passenger', 'driver'])]
    private ?string $usertype = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?float $rating = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?int $totalRides = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $profilePicture = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

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

    public function getUsertype(): ?string
    {
        return $this->usertype;
    }

    public function setUsertype(string $usertype): static
    {
        $this->usertype = $usertype;

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

    // UserInterface methods
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}

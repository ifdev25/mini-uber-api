<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\RatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['rating:read']],
    denormalizationContext: ['groups' => ['rating:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_USER') and object.getRater() == user"),
        new Delete(security: "is_granted('ROLE_USER') and object.getRater() == user")
    ]
)]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[Groups(['rating:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['rating:read', 'rating:write'])]
    #[Assert\NotBlank]
    private ?Ride $ride = null;

    // Utilisateur qui donne la note
    #[ORM\ManyToOne(inversedBy: 'ratingsGiven')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['rating:read', 'rating:write'])]
    #[Assert\NotBlank]
    private ?User $rater = null;

    // Utilisateur qui reçoit la note
    #[ORM\ManyToOne(inversedBy: 'ratingsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['rating:read', 'rating:write'])]
    #[Assert\NotBlank]
    private ?User $rated = null;

    #[ORM\Column(name: 'score', type: 'float')]
    #[Groups(['rating:read', 'rating:write'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    private ?float $score = null;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    #[Groups(['rating:read', 'rating:write'])]
    #[Assert\Length(max: 1000)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRide(): ?Ride
    {
        return $this->ride;
    }

    public function setRide(?Ride $ride): static
    {
        $this->ride = $ride;

        return $this;
    }

    public function getRater(): ?User
    {
        return $this->rater;
    }

    public function setRater(?User $rater): static
    {
        $this->rater = $rater;

        return $this;
    }

    public function getRated(): ?User
    {
        return $this->rated;
    }

    public function setRated(?User $rated): static
    {
        $this->rated = $rated;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Coordinates;
use App\Entity\Email;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: "restaurant", options: ["engine" => "InnoDB"])]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $dateCreation;

    #[ORM\Column(type: "boolean")]
    private bool $actif = true;

    #[ORM\Embedded(class: Coordinates::class, columnPrefix: false)]
    private Coordinates $coordinates;

    #[ORM\Column(type: "float")]
    private float $rating = 0.0;

    #[ORM\Column(type: "boolean")]
    private bool $isOpen = true;

    #[ORM\Column(type: "integer")]
    private int $nombrePlaces = 50;

    public function __construct()
    {
        $this->dateCreation = new DateTimeImmutable();
        $this->coordinates = new Coordinates();
        $this->email = new Email();
    }

    // ---------------- GETTERS / SETTERS ----------------

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getEmail(): Email { return $this->email; }
    public function setEmail(Email $email): static { $this->email = $email; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getDateCreation(): DateTimeImmutable { return $this->dateCreation; }
    protected function setDateCreation(DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

    public function getCoordinates(): Coordinates { return $this->coordinates; }
    public function setCoordinates(Coordinates $coordinates): static { $this->coordinates = $coordinates; return $this; }

    public function getRating(): float { return $this->rating; }
    public function setRating(float $rating): static { $this->rating = $rating; return $this; }

    public function isOpen(): bool { return $this->isOpen; }
    public function setIsOpen(bool $isOpen): static { $this->isOpen = $isOpen; return $this; }

    public function getNombrePlaces(): int { return $this->nombrePlaces; }
    public function setNombrePlaces(int $nombrePlaces): static { $this->nombrePlaces = $nombrePlaces; return $this; }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Menu
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Restaurant::class)]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id', nullable: false)]
    private Restaurant $restaurant;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $restaurantNom = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "boolean")]
    private bool $actif = true;



    /** @var array<int, int> */
    #[ORM\Column(type: "json")]
    private array $dishesIds = [];

    // ================= CONSTRUCTEUR

    public function __construct()
    {
        $this->actif = true;
        $this->dishesIds = [];
    }

    // ================= VALIDATION (équivalent Java)

    /** @return array<string, string> */
    public function validate(): array
    {
        $errors = [];

        if (!$this->nom || trim($this->nom) === '') {
            $errors['nom'] = "Le nom du menu est obligatoire.";
        } elseif (strlen($this->nom) > 100) {
            $errors['nom'] = "Le nom ne doit pas dépasser 100 caractères.";
        }

        if (!$this->restaurant) {
            $errors['restaurant'] = "Restaurant obligatoire.";
        }

        if ($this->prix !== null) {
            if ((float)$this->prix < 0) {
                $errors['prix'] = "Prix négatif interdit.";
            } elseif ((float)$this->prix > 999999.99) {
                $errors['prix'] = "Prix trop élevé.";
            }
        }

        if (!$this->description || trim($this->description) === '') {
            $errors['description'] = "Description obligatoire.";
        } elseif (strlen($this->description) > 500) {
            $errors['description'] = "Max 500 caractères.";
        }

        if (!$this->dateDebut || !$this->dateFin) {
            $errors['dates'] = "Dates obligatoires.";
        } elseif ($this->dateDebut > $this->dateFin) {
            $errors['dates'] = "Date début > date fin.";
        }

        return $errors;
    }

    // ================= LIFECYCLE (auto update)



    // ================= LOGIQUE MÉTIER

    public function estActifMaintenant(): bool
    {
        $now = new \DateTime();

        return $this->actif &&
            $this->dateDebut <= $now &&
            $this->dateFin >= $now;
    }

    // ================= GETTERS / SETTERS

    public function getId(): ?int { return $this->id; }

    public function getRestaurant(): Restaurant { return $this->restaurant; }
    public function setRestaurant(Restaurant $restaurant): static { $this->restaurant = $restaurant; return $this; }

    public function getRestaurantNom(): ?string { return $this->restaurantNom; }
    public function setRestaurantNom(?string $restaurantNom): static { $this->restaurantNom = $restaurantNom; return $this; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(?string $prix): static { $this->prix = $prix; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    protected function setDateDebut(?\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    protected function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }



    /** @return array<int, int> */
    public function getDishesIds(): array { return $this->dishesIds; }
    /** @param array<int, int> $dishesIds */
    public function setDishesIds(array $dishesIds): static { $this->dishesIds = $dishesIds; return $this; }

    // ================= toString

    public function __toString(): string
    {
        return $this->nom . ($this->prix ? " (" . $this->prix . " €)" : "");
    }

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }
}
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name:"repas_detaille")]
class RepasDetaille
{
    public const TYPES_REPAS = ['PETIT_DEJEUNER','DEJEUNER','DINER','SNACK'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:120)]
    private string $nom;

    #[ORM\Column(type:"text")]
    private string $description;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private string $prix;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $calories = null;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $proteines = null;

    #[ORM\Column(type:"string", length:50, nullable:true)]
    private ?string $typeRepas = null;

    #[ORM\Column(type:"date", nullable:true)]
    private ?DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: true)]
    private ?Participation $participant = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id_event', nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: Restaurant::class)]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id', nullable: true)]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToOne(targetEntity: Menu::class)]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id', nullable: true)]
    private ?Menu $menu = null;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $tempsPreparation = null;

    /** @var array<string> */
    #[ORM\Column(type:"json", nullable:true)]
    private array $ingredients = [];

    /** @var array<string> */
    #[ORM\Column(type:"json", nullable:true)]
    private array $allergenes = [];

    #[ORM\Column(type:"boolean")]
    private bool $vegetarien = false;

    #[ORM\Column(type:"boolean")]
    private bool $vegan = false;

    #[ORM\Column(type:"boolean")]
    private bool $sansGluten = false;

    #[ORM\Column(type:"boolean")]
    private bool $halal = false;

    #[ORM\Column(type:"boolean")]
    private bool $actif = true;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $notes = null;

    #[ORM\Column(type:"integer")]
    private int $choixCount = 0;

    public function __construct() {
        $this->ingredients = [];
        $this->allergenes = [];
    }

    // -------- GETTERS / SETTERS --------

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $desc): static { $this->description = $desc; return $this; }

    public function getPrix(): string { return $this->prix; }
    public function setPrix(string $prix): static { $this->prix = $prix; return $this; }

    public function getCalories(): ?int { return $this->calories; }
    public function setCalories(?int $c): static { $this->calories = $c; return $this; }

    public function getProteines(): ?int { return $this->proteines; }
    public function setProteines(?int $p): static { $this->proteines = $p; return $this; }

    public function getTypeRepas(): ?string { return $this->typeRepas; }
    public function setTypeRepas(?string $t): static { $this->typeRepas = $t; return $this; }

    public function getDate(): ?DateTimeInterface { return $this->date; }
    protected function setDate(?DateTimeInterface $d): static { $this->date = $d; return $this; }

    public function getParticipant(): ?Participation { return $this->participant; }
    public function setParticipant(?Participation $p): static { $this->participant = $p; return $this; }

    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $e): static { $this->evenement = $e; return $this; }

    public function getRestaurant(): ?Restaurant { return $this->restaurant; }
    public function setRestaurant(?Restaurant $r): static { $this->restaurant = $r; return $this; }

    public function getMenu(): ?Menu { return $this->menu; }
    public function setMenu(?Menu $m): static { $this->menu = $m; return $this; }

    public function getTempsPreparation(): ?int { return $this->tempsPreparation; }
    public function setTempsPreparation(?int $t): static { $this->tempsPreparation = $t; return $this; }

    /** @return array<string> */
    public function getIngredients(): array { return $this->ingredients; }
    /** @param array<string> $i */
    public function setIngredients(array $i): static { $this->ingredients = $i; return $this; }

    /** @return array<string> */
    public function getAllergenes(): array { return $this->allergenes; }
    /** @param array<string> $a */
    public function setAllergenes(array $a): static { $this->allergenes = $a; return $this; }

    public function isVegetarien(): bool { return $this->vegetarien; }
    public function setVegetarien(bool $b): static { $this->vegetarien = $b; return $this; }

    public function isVegan(): bool { return $this->vegan; }
    public function setVegan(bool $b): static { $this->vegan = $b; return $this; }

    public function isSansGluten(): bool { return $this->sansGluten; }
    public function setSansGluten(bool $b): static { $this->sansGluten = $b; return $this; }

    public function isHalal(): bool { return $this->halal; }
    public function setHalal(bool $b): static { $this->halal = $b; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $b): static { $this->actif = $b; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $s): static { $this->imageUrl = $s; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $s): static { $this->notes = $s; return $this; }

    public function getChoixCount(): int { return $this->choixCount; }
    public function setChoixCount(int $n): static { $this->choixCount = $n; return $this; }
}
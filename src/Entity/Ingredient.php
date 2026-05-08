<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 50)]
    private string $categorie;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private string $prixSupplement;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $calories = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $proteines = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $iconUrl = null;

    #[ORM\Column(type: "integer")]
    private int $stockQuantite = 0;

    #[ORM\Column(type: "integer")]
    private int $stockSeuilAlerte = 0;

    #[ORM\Column(type: "boolean")]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: RepasDetaille::class)]
    #[ORM\JoinColumn(name: "repas_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?RepasDetaille $repas = null;

    // ================= ENUM (équivalent Java)
    public const CATEGORIE_BASE = 'BASE';
    public const CATEGORIE_PROTEINE = 'PROTEINE';
    public const CATEGORIE_LEGUME = 'LEGUME';
    public const CATEGORIE_SAUCE = 'SAUCE';
    public const CATEGORIE_TOPPING = 'TOPPING';
    public const CATEGORIE_EXTRA = 'EXTRA';

    public const CATEGORIES = [
        self::CATEGORIE_BASE,
        self::CATEGORIE_PROTEINE,
        self::CATEGORIE_LEGUME,
        self::CATEGORIE_SAUCE,
        self::CATEGORIE_TOPPING,
        self::CATEGORIE_EXTRA,
    ];

    // ================= CONSTRUCTEUR

    public function __construct()
    {
        $this->actif = true;
        $this->stockQuantite = 0;
        $this->stockSeuilAlerte = 0;
    }

    public function initialiser(
        string $nom,
        string $categorie,
        string $prixSupplement,
        ?int $calories,
        ?int $proteines = null
    ): void {
        $this->nom = $nom;
        $this->categorie = $categorie;
        $this->prixSupplement = $prixSupplement;
        $this->calories = $calories;
        $this->proteines = $proteines;
    }

    // ================= LOGIQUE MÉTIER

    public function estEnRupture(): bool
    {
        return $this->stockQuantite <= 0;
    }

    public function estSousSeuil(): bool
    {
        return $this->stockQuantite <= $this->stockSeuilAlerte;
    }

    // ================= GETTERS / SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getPrixSupplement(): string
    {
        return $this->prixSupplement;
    }

    public function setPrixSupplement(string $prixSupplement): static
    {
        $this->prixSupplement = $prixSupplement;
        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function setCalories(?int $calories): static
    {
        $this->calories = $calories;
        return $this;
    }

    public function getProteines(): ?int
    {
        return $this->proteines;
    }

    public function setProteines(?int $proteines): static
    {
        $this->proteines = $proteines;
        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): static
    {
        $this->iconUrl = $iconUrl;
        return $this;
    }

    public function getStockQuantite(): int
    {
        return $this->stockQuantite;
    }

    public function setStockQuantite(int $stockQuantite): static
    {
        $this->stockQuantite = $stockQuantite;
        return $this;
    }

    public function getStockSeuilAlerte(): int
    {
        return $this->stockSeuilAlerte;
    }

    public function setStockSeuilAlerte(int $stockSeuilAlerte): static
    {
        $this->stockSeuilAlerte = $stockSeuilAlerte;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getRepas(): ?RepasDetaille
    {
        return $this->repas;
    }

    public function setRepas(?RepasDetaille $repas): static
    {
        $this->repas = $repas;
        return $this;
    }

    // ================= EQUIVALENT equals()

    public function equals(?Ingredient $other): bool
    {
        if ($other === null) {
            return false;
        }

        if ($this->id !== null && $other->getId() !== null) {
            return $this->id === $other->getId();
        }

        return $this->nom === $other->getNom();
    }

    // ================= toString()

    public function __toString(): string
    {
        return $this->nom . " (+" . $this->prixSupplement . " €)";
    }
}
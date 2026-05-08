<?php

namespace App\Entity;

use App\Entity\Users;
use App\Repository\EquipementsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Aligné sur la table `equipements` (Lamma Boutique).
 */
#[ORM\Entity(repositoryClass: EquipementsRepository::class)]
#[ORM\Table(name: 'equipements')]
class Equipements
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private string $nom;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type (Vente/Location) est obligatoire.')]
    private string $type;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0.')]
    private string $prix;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 20, options: ['default' => 'DISPONIBLE'])]
    private string $statut = 'DISPONIBLE';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAjout = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $mail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $caracteristiques = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $nombreVues = 0;

    /** @var Collection<int, EquipementAttribut> */
    #[ORM\OneToMany(mappedBy: 'equipement', targetEntity: EquipementAttribut::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $attributs;

    #[ORM\OneToOne(mappedBy: 'equipement', targetEntity: Delivery::class, cascade: ['persist', 'remove'])]
    private ?Delivery $delivery = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    private ?Users $owner = null;

    #[ORM\Column]
    private bool $livrable = false;

    public function __construct()
    {
        $this->dateAjout = new \DateTime();
        $this->attributs = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    protected function setDateAjout(?\DateTimeInterface $dateAjout): static
    {
        if ($dateAjout instanceof \DateTimeImmutable) {
            $dateAjout = \DateTime::createFromImmutable($dateAjout);
        }
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getCaracteristiques(): ?string
    {
        return $this->caracteristiques;
    }

    public function setCaracteristiques(?string $caracteristiques): static
    {
        $this->caracteristiques = $caracteristiques;

        return $this;
    }

    public function getNombreVues(): int
    {
        return $this->nombreVues;
    }

    public function setNombreVues(int $nombreVues): static
    {
        $this->nombreVues = $nombreVues;

        return $this;
    }

    /** @return Collection<int, EquipementAttribut> */
    public function getAttributs(): Collection
    {
        return $this->attributs;
    }

    public function addAttribut(EquipementAttribut $attribut): static
    {
        if (!$this->attributs->contains($attribut)) {
            $this->attributs->add($attribut);
            $attribut->setEquipement($this);
        }

        return $this;
    }

    public function removeAttribut(EquipementAttribut $attribut): static
    {
        $this->attributs->removeElement($attribut);

        return $this;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function setDelivery(?Delivery $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getOwner(): ?Users
    {
        return $this->owner;
    }

    public function setOwner(?Users $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function isLivrable(): bool
    {
        return $this->livrable;
    }

    public function setLivrable(bool $livrable): static
    {
        $this->livrable = $livrable;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Entity\Equipements;
use App\Entity\Users;
use App\Entity\Coordinates;
use App\Repository\DeliveryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
#[ORM\Table(name: 'delivery')]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'delivery', targetEntity: Equipements::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Equipements $equipement;

    #[ORM\ManyToOne(inversedBy: 'livraisons')]
    private ?Users $acheteur = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $estimation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateLivraison = null;

    #[ORM\Embedded(class: Coordinates::class, columnPrefix: false)]
    private Coordinates $coordinates;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rue = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'en_cours';



    #[ORM\Column(nullable: true)]
    private ?float $fraisLivraison = null;

    public function __construct()
    {
        $this->coordinates = new Coordinates();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipement(): Equipements
    {
        return $this->equipement;
    }

    public function setEquipement(Equipements $equipement): static
    {
        $this->equipement = $equipement;

        return $this;
    }

    public function getAcheteur(): ?Users
    {
        return $this->acheteur;
    }

    public function setAcheteur(?Users $acheteur): static
    {
        $this->acheteur = $acheteur;

        return $this;
    }

    public function getEstimation(): ?\DateTimeImmutable
    {
        return $this->estimation;
    }

    protected function setEstimation(?\DateTimeImmutable $estimation): static
    {
        $this->estimation = $estimation;

        return $this;
    }

    public function getDateLivraison(): ?\DateTimeImmutable
    {
        return $this->dateLivraison;
    }

    protected function setDateLivraison(?\DateTimeImmutable $dateLivraison): static
    {
        $this->dateLivraison = $dateLivraison;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(?string $rue): static
    {
        $this->rue = $rue;

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

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

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

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    public function setCoordinates(Coordinates $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getFraisLivraison(): ?float
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(?float $fraisLivraison): static
    {
        $this->fraisLivraison = $fraisLivraison;

        return $this;
    }
}

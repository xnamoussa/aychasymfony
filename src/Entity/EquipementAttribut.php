<?php

namespace App\Entity;

use App\Repository\EquipementAttributRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipementAttributRepository::class)]
#[ORM\Table(name: 'equipement_attributs')]
class EquipementAttribut
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attributs')]
    #[ORM\JoinColumn(name: 'equipement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Equipements $equipement;

    #[ORM\Column(name: 'nom_attribut', length: 100)]
    private string $nomAttribut;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

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

    public function getNomAttribut(): string
    {
        return $this->nomAttribut;
    }

    public function setNomAttribut(string $nomAttribut): static
    {
        $this->nomAttribut = $nomAttribut;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(?string $valeur): static
    {
        $this->valeur = $valeur;

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
}

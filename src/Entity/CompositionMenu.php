<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name:"composition_menu")]
class CompositionMenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:Restauration::class)]
    #[ORM\JoinColumn(name:"menu_id", referencedColumnName:"id", nullable:true)]
    private ?Restauration $menu = null;

    #[ORM\ManyToOne(targetEntity:RepasDetaille::class)]
    #[ORM\JoinColumn(name:"repas_id", referencedColumnName:"id", nullable:true, onDelete: "CASCADE")]
    private ?RepasDetaille $repas = null;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $ordre = null;

    #[ORM\Column(type:"string", length:50, nullable:true)]
    private ?string $typeRepas = null;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type:"boolean")]
    private bool $actif = true;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $notes = null;

    // ========== GETTERS / SETTERS ==========

    public function getId(): ?int { return $this->id; }

    public function getMenu(): ?Restauration { return $this->menu; }
    public function setMenu(?Restauration $menu): self { $this->menu = $menu; return $this; }

    public function getRepas(): ?RepasDetaille { return $this->repas; }
    public function setRepas(?RepasDetaille $repas): self { $this->repas = $repas; return $this; }

    public function getOrdre(): ?int { return $this->ordre; }
    public function setOrdre(?int $ordre): self { $this->ordre = $ordre; return $this; }

    public function getTypeRepas(): ?string { return $this->typeRepas; }
    public function setTypeRepas(?string $type): self { $this->typeRepas = $type; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $d): self { $this->date = $d; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $b): self { $this->actif = $b; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): self { $this->notes = $n; return $this; }
}
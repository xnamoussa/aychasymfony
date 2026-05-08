<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Evenement;

#[ORM\Entity]
class Equipment
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

        #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: "equipments")]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_event', onDelete: 'CASCADE')]
    private ?Evenement $event_id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $libelle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getEvent_id(): ?Evenement
    {
        return $this->event_id;
    }

    public function setEvent_id(?Evenement $event_id): static
    {
        $this->event_id = $event_id;
        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }
}

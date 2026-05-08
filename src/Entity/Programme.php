<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


use App\Entity\Evenement;

#[ORM\Entity]
class Programme
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_prog = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: "programmes")]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_event', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le programme doit être rattaché à un événement.")]
    private Evenement $event_id;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le titre du programme est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le titre doit comporter au moins {{ limit }} caractères.")]
    private string $titre;

    #[ORM\Column(type: "datetime_immutable")]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    private \DateTimeImmutable $date_debut;

    #[ORM\Column(type: "datetime_immutable")]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\GreaterThanOrEqual(propertyPath: "date_debut", message: "La date de fin doit être ultérieure ou égale à la date de début.")]
    private \DateTimeImmutable $date_fin;


    public function getId_prog(): ?int
    {
        return $this->id_prog;
    }

    public function getIdProg(): ?int
    {
        return $this->id_prog;
    }

    public function setId_prog(?int $id_prog): static
    {
        $this->id_prog = $id_prog;
        return $this;
    }

    public function setIdProg(?int $id_prog): static
    {
        $this->id_prog = $id_prog;
        return $this;
    }

    public function getEvent_id(): Evenement
    {
        return $this->event_id;
    }

    public function getEventId(): Evenement
    {
        return $this->event_id;
    }

    public function setEvent_id(Evenement $event_id): static
    {
        $this->event_id = $event_id;
        return $this;
    }

    public function setEventId(Evenement $event_id): static
    {
        $this->event_id = $event_id;
        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDate_debut(): \DateTimeImmutable { return $this->date_debut; }
    public function getDateDebut(): \DateTimeImmutable { return $this->date_debut; }
    protected function setDate_debut(\DateTimeImmutable $date_debut): static { $this->date_debut = $date_debut; return $this; }
    protected function setDateDebut(\DateTimeImmutable $date_debut): static { $this->date_debut = $date_debut; return $this; }

    public function getDate_fin(): \DateTimeImmutable { return $this->date_fin; }
    public function getDateFin(): \DateTimeImmutable { return $this->date_fin; }
    protected function setDate_fin(\DateTimeImmutable $date_fin): static { $this->date_fin = $date_fin; return $this; }
    protected function setDateFin(\DateTimeImmutable $date_fin): static { $this->date_fin = $date_fin; return $this; }
}

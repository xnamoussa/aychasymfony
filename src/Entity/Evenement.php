<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\ReservationMaquillage;
use App\Entity\EventSponsor;

use App\Entity\Equipment;
use App\Entity\Programme;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
class Evenement
{

    public function __construct()
    {
        $this->equipments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->programmes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reservationMaquillages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->eventSponsors = new \Doctrine\Common\Collections\ArrayCollection();

        $this->nb_vues = 0;
        $this->propose_makeup = false;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_event = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le titre doit contenir au moins {{ limit }} caractères.")]
    private string $titre;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins {{ limit }} caractères.")]
    private string $description;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le type est obligatoire.")]
    private string $type;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\GreaterThanOrEqual(propertyPath: "date_debut", message: "La date de fin doit être après ou égale à la date de début.")]
    private \DateTimeInterface $date_fin;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    private string $lieu;

    #[ORM\Column(type: "string", length: 1000, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'evenements', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $spotify_url = null;

    #[ORM\Column(type: "boolean", nullable: true, options: ["default" => false])]
    private ?bool $propose_makeup = false;

    #[ORM\Column(type: "integer")]
    private int $nb_vues = 0;

    public function getId_event(): ?int { return $this->id_event; }
    public function getIdEvent(): ?int { return $this->id_event; }
    public function setId_event(?int $id_event): static { $this->id_event = $id_event; return $this; }
    public function setIdEvent(?int $id_event): static { $this->id_event = $id_event; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDate_debut(): \DateTimeInterface { return $this->date_debut; }
    public function getDateDebut(): \DateTimeInterface { return $this->date_debut; }
    public function setDate_debut(\DateTimeInterface $date_debut): static { $this->date_debut = $date_debut; return $this; }
    public function setDateDebut(\DateTimeInterface $date_debut): static { $this->date_debut = $date_debut; return $this; }

    public function getDate_fin(): \DateTimeInterface { return $this->date_fin; }
    public function getDateFin(): \DateTimeInterface { return $this->date_fin; }
    public function setDate_fin(\DateTimeInterface $date_fin): static { $this->date_fin = $date_fin; return $this; }
    public function setDateFin(\DateTimeInterface $date_fin): static { $this->date_fin = $date_fin; return $this; }

    public function getLieu(): string { return $this->lieu; }
    public function setLieu(string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File { return $this->imageFile; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    protected function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getSpotify_url(): ?string { return $this->spotify_url; }
    public function getSpotifyUrl(): ?string { return $this->spotify_url; }
    public function setSpotify_url(?string $spotify_url): static { $this->spotify_url = $spotify_url; return $this; }
    public function setSpotifyUrl(?string $spotify_url): static { $this->spotify_url = $spotify_url; return $this; }

    public function getNb_vues(): int { return $this->nb_vues; }
    public function getNbVues(): int { return $this->nb_vues; }
    public function setNb_vues(int $nb_vues): static { $this->nb_vues = $nb_vues; return $this; }
    public function setNbVues(int $nb_vues): static { $this->nb_vues = $nb_vues; return $this; }

    public function isProposeMakeup(): ?bool { return $this->propose_makeup; }
    public function setProposeMakeup(?bool $propose_makeup): static { $this->propose_makeup = $propose_makeup; return $this; }

    /** @var Collection<int, Equipment> */
    #[ORM\OneToMany(mappedBy: "event_id", targetEntity: Equipment::class, cascade: ["remove"])]
    private Collection $equipments;

    /** @return Collection<int, Equipment> */
    public function getEquipments(): Collection { return $this->equipments; }

    public function addEquipment(Equipment $equipment): static
    {
        if (!$this->equipments->contains($equipment)) {
            $this->equipments[] = $equipment;
            $equipment->setEvent_id($this);
        }
        return $this;
    }

    public function removeEquipment(Equipment $equipment): static
    {
        $this->equipments->removeElement($equipment);
        return $this;
    }

    /** @var Collection<int, Programme> */
    #[ORM\OneToMany(mappedBy: "event_id", targetEntity: Programme::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $programmes;

    /** @return Collection<int, Programme> */
    public function getProgrammes(): Collection { return $this->programmes; }

    public function addProgramme(Programme $programme): static
    {
        if (!$this->programmes->contains($programme)) {
            $this->programmes[] = $programme;
            $programme->setEvent_id($this);
        }
        return $this;
    }

    public function removeProgramme(Programme $programme): static
    {
        $this->programmes->removeElement($programme);
        return $this;
    }

    /** @var Collection<int, ReservationMaquillage> */
    #[ORM\OneToMany(mappedBy: "event", targetEntity: ReservationMaquillage::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $reservationMaquillages;

    /** @return Collection<int, ReservationMaquillage> */
    public function getReservationMaquillages(): Collection { return $this->reservationMaquillages; }

    public function addReservationMaquillage(ReservationMaquillage $reservationMaquillage): static
    {
        if (!$this->reservationMaquillages->contains($reservationMaquillage)) {
            $this->reservationMaquillages[] = $reservationMaquillage;
            $reservationMaquillage->setEvent($this);
        }
        return $this;
    }

    public function removeReservationMaquillage(ReservationMaquillage $reservationMaquillage): static
    {
        $this->reservationMaquillages->removeElement($reservationMaquillage);
        return $this;
    }

    /** @var Collection<int, EventSponsor> */
    #[ORM\OneToMany(targetEntity: EventSponsor::class, mappedBy: 'event', cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $eventSponsors;

    /** @return Collection<int, EventSponsor> */
    public function getEventSponsors(): Collection { return $this->eventSponsors; }

    public function addEventSponsor(EventSponsor $eventSponsor): static
    {
        if (!$this->eventSponsors->contains($eventSponsor)) {
            $this->eventSponsors->add($eventSponsor);
            $eventSponsor->setEvent($this);
        }
        return $this;
    }

    public function removeEventSponsor(EventSponsor $eventSponsor): static
    {
        $this->eventSponsors->removeElement($eventSponsor);
        return $this;
    }
}

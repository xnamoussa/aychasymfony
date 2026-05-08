<?php

namespace App\Entity;

use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Email;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsor')]
#[UniqueEntity(fields: ['email'], message: "Cet email est déjà utilisé par un autre sponsor")]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom doit avoir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $nom;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le statut est obligatoire")]
    private bool $statut;

    /** @var Collection<int, EventSponsor> */
    #[ORM\OneToMany(targetEntity: EventSponsor::class, mappedBy: 'sponsor', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $eventSponsors;

    /** @var Collection<int, SponsorFeedback> */
    #[ORM\OneToMany(mappedBy: 'sponsor', targetEntity: SponsorFeedback::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $feedbacks;

    public function __construct()
    {
        $this->eventSponsors = new ArrayCollection();
        $this->feedbacks = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
        $this->email = new Email();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getEmail(): Email { return $this->email; }
    public function setEmail(Email $email): static { $this->email = $email; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }
    protected function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function isStatut(): bool { return $this->statut; }
    public function setStatut(bool $statut): static { $this->statut = $statut; return $this; }

    /** @return Collection<int, EventSponsor> */
    public function getEventSponsors(): Collection { return $this->eventSponsors; }

    public function addEventSponsor(EventSponsor $eventSponsor): static
    {
        if (!$this->eventSponsors->contains($eventSponsor)) {
            $this->eventSponsors->add($eventSponsor);
            $eventSponsor->setSponsor($this);
        }
        return $this;
    }

    public function removeEventSponsor(EventSponsor $eventSponsor): static
    {
        $this->eventSponsors->removeElement($eventSponsor);
        return $this;
    }

    public function addFeedback(SponsorFeedback $feedback): static
{
    if (!$this->feedbacks->contains($feedback)) {
        $this->feedbacks->add($feedback);
        $feedback->setSponsor($this);
    }
    return $this;
}
 
public function removeFeedback(SponsorFeedback $feedback): static
{
        $this->feedbacks->removeElement($feedback);
        return $this;
}



    #[ORM\Column(length: 100, nullable: true)]
    // #[ \Symfony\Component\Serializer\Attribute\Ignore]
    private ?string $verificationToken = null;

// Getters & Setters
public function getVerificationToken(): ?string 
{ 
    return $this->verificationToken; 
}

public function setVerificationToken(?string $verificationToken): static 
{ 
    $this->verificationToken = $verificationToken; 
    return $this; 
}

}
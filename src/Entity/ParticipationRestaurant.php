<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ParticipationRestaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // ================= LIENS

    #[ORM\ManyToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false)]
    private Participation $participant;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id_event', nullable: false)]
    private Evenement $evenement;

    // ================= BESOIN SPECIFIQUE

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $besoinLibelle = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $besoinDescription = null;

    // ================= RESTRICTION ALIMENTAIRE

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $restrictionLibelle = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $restrictionDescription = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $niveauGravite = null;

    #[ORM\Column(type: "boolean")]
    private bool $restrictionActive = true;

    // ================= CHOIX REPAS

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $menuPropositionId = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateChoix = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateLimiteModification = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $commentaire = null;

    // ================= ETAT

    #[ORM\Column(type: "boolean")]
    private bool $annule = false;

    // ================= ENUM (comme Java)

    public const GRAVITE_LEGERE = 'LEGERE';
    public const GRAVITE_MODEREE = 'MODEREE';
    public const GRAVITE_SEVERE = 'SEVERE';

    // ================= CONSTRUCTEUR

    public function __construct()
    {
        $this->restrictionActive = true;
        $this->annule = false;
    }

    // ================= LOGIQUE MÉTIER

    public function estActif(): bool
    {
        return !$this->annule;
    }

    public function aRestrictionSevere(): bool
    {
        return $this->restrictionActive &&
            $this->niveauGravite === self::GRAVITE_SEVERE;
    }

    public function peutModifierChoix(): bool
    {
        if (!$this->dateLimiteModification) return false;

        return new \DateTime() <= $this->dateLimiteModification;
    }

    public function annuler(): void
    {
        $this->annule = true;
    }

    // ================= GETTERS / SETTERS

    public function getId(): ?int { return $this->id; }

    public function getParticipant(): Participation { return $this->participant; }
    public function setParticipant(Participation $participant): static { $this->participant = $participant; return $this; }

    public function getEvenement(): Evenement { return $this->evenement; }
    public function setEvenement(Evenement $evenement): static { $this->evenement = $evenement; return $this; }

    public function getBesoinLibelle(): ?string { return $this->besoinLibelle; }
    public function setBesoinLibelle(?string $besoinLibelle): static { $this->besoinLibelle = $besoinLibelle; return $this; }

    public function getBesoinDescription(): ?string { return $this->besoinDescription; }
    public function setBesoinDescription(?string $besoinDescription): static { $this->besoinDescription = $besoinDescription; return $this; }

    public function getRestrictionLibelle(): ?string { return $this->restrictionLibelle; }
    public function setRestrictionLibelle(?string $restrictionLibelle): static { $this->restrictionLibelle = $restrictionLibelle; return $this; }

    public function getRestrictionDescription(): ?string { return $this->restrictionDescription; }
    public function setRestrictionDescription(?string $restrictionDescription): static { $this->restrictionDescription = $restrictionDescription; return $this; }

    public function getNiveauGravite(): ?string { return $this->niveauGravite; }
    public function setNiveauGravite(?string $niveauGravite): static { $this->niveauGravite = $niveauGravite; return $this; }

    public function isRestrictionActive(): bool { return $this->restrictionActive; }
    public function setRestrictionActive(bool $restrictionActive): static { $this->restrictionActive = $restrictionActive; return $this; }

    public function getMenuPropositionId(): ?int { return $this->menuPropositionId; }
    public function setMenuPropositionId(?int $menuPropositionId): static { $this->menuPropositionId = $menuPropositionId; return $this; }

    public function getDateChoix(): ?\DateTimeInterface { return $this->dateChoix; }
    protected function setDateChoix(?\DateTimeInterface $dateChoix): static { $this->dateChoix = $dateChoix; return $this; }

    public function getDateLimiteModification(): ?\DateTimeInterface { return $this->dateLimiteModification; }
    protected function setDateLimiteModification(?\DateTimeInterface $dateLimiteModification): static { $this->dateLimiteModification = $dateLimiteModification; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function isAnnule(): bool { return $this->annule; }
    public function setAnnule(bool $annule): static { $this->annule = $annule; return $this; }

    public function __toString(): string
    {
        return sprintf(
            "ParticipantRestauration{id=%d, participant=%d, evenement=%d, annule=%s}",
            $this->id,
            $this->participant->getId(),
            $this->evenement->getId_event(),
            $this->annule ? 'true' : 'false'
        );
    }
}
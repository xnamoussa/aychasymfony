<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProgrammeRecommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // ---------------- RELATION PARTICIPATION
    #[ORM\ManyToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'participation_id', referencedColumnName: 'id', nullable: false)]
    private Participation $participation;

    // ---------------- ACTIVITÉ
    #[ORM\Column(type: "string", length: 255)]
    private string $activite;

    #[ORM\Column(type: "time")]
    private \DateTimeInterface $heureDebut;

    #[ORM\Column(type: "time")]
    private \DateTimeInterface $heureFin;

    // ---------------- AMBIANCE
    public const AMBIANCE_CALME = 'CALME';
    public const AMBIANCE_FESTIVE = 'FESTIVE';
    public const AMBIANCE_SOCIALE = 'SOCIALE';
    public const AMBIANCE_AVENTURE = 'AVENTURE';
    public const AMBIANCE_CULTURELLE = 'CULTURELLE';

    #[ORM\Column(type: "string", length: 50)]
    private string $ambiance;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(type: "boolean")]
    private bool $recommande = true;

    // ---------------- CONSTRUCTEUR
    public function __construct(
        Participation $participation,
        string $activite = '',
        \DateTimeInterface $heureDebut = null,
        \DateTimeInterface $heureFin = null,
        string $ambiance = self::AMBIANCE_CALME,
        ?string $justification = null
    ) {
        $this->participation = $participation;
        $this->activite = $activite;
        if ($heureDebut) $this->heureDebut = $heureDebut;
        if ($heureFin) $this->heureFin = $heureFin;
        $this->ambiance = $ambiance;
        $this->justification = $justification;
        $this->recommande = true;
    }

    // ---------------- VALIDATION METIER
    public function estValide(): bool
    {
        return isset($this->heureDebut, $this->heureFin) &&
               $this->heureFin > $this->heureDebut;
    }

    // ---------------- GETTERS / SETTERS
    public function getId(): ?int { return $this->id; }

    public function getParticipation(): Participation { return $this->participation; }
    public function setParticipation(Participation $participation): void { $this->participation = $participation; }

    public function getActivite(): string { return $this->activite; }
    public function setActivite(string $activite): void { $this->activite = $activite; }

    public function getHeureDebut(): \DateTimeInterface { return $this->heureDebut; }
    protected function setHeureDebut(\DateTimeInterface $heureDebut): void { $this->heureDebut = $heureDebut; }

    public function getHeureFin(): \DateTimeInterface { return $this->heureFin; }
    protected function setHeureFin(\DateTimeInterface $heureFin): void { $this->heureFin = $heureFin; }

    public function getAmbiance(): string { return $this->ambiance; }
    public function setAmbiance(string $ambiance): void { $this->ambiance = $ambiance; }

    public function getJustification(): ?string { return $this->justification; }
    public function setJustification(?string $justification): void { $this->justification = $justification; }

    public function isRecommande(): bool { return $this->recommande; }
    public function setRecommande(bool $recommande): void { $this->recommande = $recommande; }

    public function __toString(): string
    {
        return sprintf(
            "Programme: %-25s | %s → %s | Ambiance: %-10s | %s",
            $this->activite,
            $this->heureDebut->format('H:i'),
            $this->heureFin->format('H:i'),
            $this->ambiance,
            $this->justification ?? ''
        );
    }
}
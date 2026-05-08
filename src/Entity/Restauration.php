<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name:"restauration")]
class Restauration
{
    public const TYPE_MENU = 'MENU';
    public const TYPE_OPTION = 'OPTION';
    public const TYPE_REPAS = 'REPAS';
    public const TYPE_RESTRICTION = 'RESTRICTION';
    public const TYPE_PRESENCE = 'PRESENCE';

    public const TYPES = [
        self::TYPE_MENU,
        self::TYPE_OPTION,
        self::TYPE_REPAS,
        self::TYPE_RESTRICTION,
        self::TYPE_PRESENCE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:20)]
    private string $type;

    #[ORM\Column(type:"boolean")]
    private bool $actif = true;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $nom = null;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $optionRestaurationId = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $libelle = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $typeEvenement = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $nomRepas = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    private ?string $prix = null;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $restrictionLibelle = null;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $restrictionDescription = null;

    #[ORM\Column(type:"date", nullable:true)]
    private ?\DateTimeInterface $datePresence = null;

    #[ORM\Column(type:"boolean")]
    private bool $abonnementActif = false;

    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $participantId = null;

    public function __construct(string $type = self::TYPE_MENU)
    {
        $this->type = $type;
    }

    // ========== FACTORY METHODS ==========

    public static function menu(string $nom, ?int $optionId, bool $actif = true): self
    {
        $m = new self(self::TYPE_MENU);
        $m->setNom($nom);
        $m->setOptionRestaurationId($optionId);
        $m->setActif($actif);
        return $m;
    }

    public static function option(string $libelle, string $typeEvenement, bool $actif = true): self
    {
        $o = new self(self::TYPE_OPTION);
        $o->setLibelle($libelle);
        $o->setTypeEvenement($typeEvenement);
        $o->setActif($actif);
        return $o;
    }

    public static function repas(string $nomRepas, float $prix, \DateTimeInterface $date, int $participantId): self
    {
        $r = new self(self::TYPE_REPAS);
        $r->setNomRepas($nomRepas);
        $r->setPrix((string)$prix);
        $r->setDate($date);
        $r->setParticipantId($participantId);
        return $r;
    }

    public static function restriction(string $libelle, string $description, bool $actif = true): self
    {
        $r = new self(self::TYPE_RESTRICTION);
        $r->setRestrictionLibelle($libelle);
        $r->setRestrictionDescription($description);
        $r->setActif($actif);
        return $r;
    }

    public static function presence(int $participantId, \DateTimeInterface $date, bool $abonnementActif = true): self
    {
        $p = new self(self::TYPE_PRESENCE);
        $p->setDatePresence($date);
        $p->setAbonnementActif($abonnementActif);
        $p->setParticipantId($participantId);
        return $p;
    }

    // ========== GETTERS / SETTERS ==========

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }

    public function getOptionRestaurationId(): ?int { return $this->optionRestaurationId; }
    public function setOptionRestaurationId(?int $id): static { $this->optionRestaurationId = $id; return $this; }

    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(?string $libelle): static { $this->libelle = $libelle; return $this; }

    public function getTypeEvenement(): ?string { return $this->typeEvenement; }
    public function setTypeEvenement(?string $typeEvenement): static { $this->typeEvenement = $typeEvenement; return $this; }

    public function getNomRepas(): ?string { return $this->nomRepas; }
    public function setNomRepas(?string $nomRepas): static { $this->nomRepas = $nomRepas; return $this; }

    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(?string $prix): static { $this->prix = $prix; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getRestrictionLibelle(): ?string { return $this->restrictionLibelle; }
    public function setRestrictionLibelle(?string $libelle): static { $this->restrictionLibelle = $libelle; return $this; }

    public function getRestrictionDescription(): ?string { return $this->restrictionDescription; }
    public function setRestrictionDescription(?string $desc): static { $this->restrictionDescription = $desc; return $this; }

    public function getDatePresence(): ?\DateTimeInterface { return $this->datePresence; }
    public function setDatePresence(?\DateTimeInterface $date): static { $this->datePresence = $date; return $this; }

    public function isAbonnementActif(): bool { return $this->abonnementActif; }
    public function setAbonnementActif(bool $actif): static { $this->abonnementActif = $actif; return $this; }

    public function getParticipantId(): ?int { return $this->participantId; }
    public function setParticipantId(?int $id): static { $this->participantId = $id; return $this; }
}
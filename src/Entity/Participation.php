<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $userId;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id_event', nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateInscription;

    // ================= ENUMS

    public const TYPE_SIMPLE = 'SIMPLE';
    public const TYPE_HEBERGEMENT = 'HEBERGEMENT';
    public const TYPE_GROUPE = 'GROUPE';

    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_CONFIRME = 'CONFIRME';
    public const STATUT_ANNULE = 'ANNULE';
    public const STATUT_LISTE_ATTENTE = 'EN_LISTE_ATTENTE';

    public const CONTEXTE_COUPLE = 'COUPLE';
    public const CONTEXTE_AMIS = 'AMIS';
    public const CONTEXTE_FAMILLE = 'FAMILLE';
    public const CONTEXTE_SOLO = 'SOLO';
    public const CONTEXTE_PRO = 'PROFESSIONNEL';

    public const MEAL_SANS = 'SANS_REPAS';
    public const MEAL_AVEC = 'AVEC_REPAS';
    public const MEAL_MENU = 'AVEC_MENU';
    public const MEAL_COMPOSITION = 'COMPOSITION_SUR_PLACE';
    public const MEAL_RESTAURANT = 'AU_RESTAURANT';

    // ✅ IMPORTANT POUR LE FORMULAIRE
    public const TYPES = [
        self::TYPE_SIMPLE,
        self::TYPE_HEBERGEMENT,
        self::TYPE_GROUPE,
    ];

    public const CONTEXTES = [
        self::CONTEXTE_COUPLE,
        self::CONTEXTE_AMIS,
        self::CONTEXTE_FAMILLE,
        self::CONTEXTE_SOLO,
        self::CONTEXTE_PRO,
    ];

    public const MEAL_OPTIONS = [
        self::MEAL_SANS,
        self::MEAL_AVEC,
        self::MEAL_MENU,
        self::MEAL_COMPOSITION,
        self::MEAL_RESTAURANT,
    ];

    // ================= CHAMPS

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(type: "integer")]
    private int $hebergementNuits = 0;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $contexteSocial = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $badgeAssocie = null;

    #[ORM\Column(type: "integer")]
    private int $nbAdultes = 0;

    #[ORM\Column(type: "integer")]
    private int $nbEnfants = 0;

    #[ORM\Column(type: "integer")]
    private int $nbChiens = 0;

    #[ORM\Column(type: "integer")]
    private int $totalParticipants = 0;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $typeAbonnementChoisi = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $montantCalcule = null;

    #[ORM\Column(type: "string", length: 10)]
    private string $devise = 'EUR';

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $besoinsSpeciaux = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $mealOption = null;

    #[ORM\Column(type: "integer")]
    private int $pointsEarned = 0;

    #[ORM\ManyToOne(targetEntity: Abonnement::class)]
    #[ORM\JoinColumn(name: 'abonnement_id', referencedColumnName: 'id', nullable: true)]
    private ?Abonnement $abonnement = null;

    #[ORM\ManyToOne(targetEntity: Restaurant::class)]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id', nullable: true)]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToOne(targetEntity: Menu::class)]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id', nullable: true)]
    private ?Menu $menu = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $raisonAnnulation = null;

    // ================= CONSTRUCTEUR

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
        $this->statut = self::STATUT_EN_ATTENTE;
    }

    // ================= MÉTIER

    public function confirmer(): void
    {
        $this->statut = self::STATUT_CONFIRME;
        $this->badgeAssocie = $this->attribuerBadge();
    }

    public function annuler(): void
    {
        $this->statut = self::STATUT_ANNULE;
    }

    public function calculerTotalParticipants(): void
    {
        $this->totalParticipants = $this->nbAdultes + $this->nbEnfants;
    }

    private function attribuerBadge(): string
    {
        return match ($this->contexteSocial) {
            self::CONTEXTE_COUPLE => "Romantique_Aventure",
            self::CONTEXTE_AMIS => "Esprit_Equipe",
            self::CONTEXTE_FAMILLE => "Famille_Unie",
            self::CONTEXTE_SOLO => "Explorateur_Solitaire",
            self::CONTEXTE_PRO => "Pro_Leadership",
            default => "Explorateur_Standard",
        };
    }

    public function estConfirmee(): bool
    {
        return $this->statut === self::STATUT_CONFIRME;
    }

    // ================= GETTERS / SETTERS

    public function getId(): int { return $this->id; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }

    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $evenement): self { $this->evenement = $evenement; return $this; }

    public function getDateInscription(): \DateTimeInterface { return $this->dateInscription; }
    protected function setDateInscription(\DateTimeInterface $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getHebergementNuits(): int { return $this->hebergementNuits; }
    public function setHebergementNuits(?int $hebergementNuits): self { $this->hebergementNuits = $hebergementNuits ?? 0; return $this; }

    public function getContexteSocial(): ?string { return $this->contexteSocial; }
    public function setContexteSocial(?string $contexteSocial): self { $this->contexteSocial = $contexteSocial; return $this; }

    public function getBadgeAssocie(): ?string { return $this->badgeAssocie; }
    public function setBadgeAssocie(?string $badgeAssocie): self { $this->badgeAssocie = $badgeAssocie; return $this; }

    public function getNbAdultes(): int { return $this->nbAdultes; }
    public function setNbAdultes(int $nbAdultes): self { $this->nbAdultes = $nbAdultes; return $this; }

    public function getNbEnfants(): int { return $this->nbEnfants; }
    public function setNbEnfants(int $nbEnfants): self { $this->nbEnfants = $nbEnfants; return $this; }

    public function getNbChiens(): int { return $this->nbChiens; }
    public function setNbChiens(int $nbChiens): self { $this->nbChiens = $nbChiens; return $this; }

    public function getTotalParticipants(): int { return $this->totalParticipants; }
    public function setTotalParticipants(int $totalParticipants): self { $this->totalParticipants = $totalParticipants; return $this; }

    public function getTypeAbonnementChoisi(): ?string { return $this->typeAbonnementChoisi; }
    public function setTypeAbonnementChoisi(?string $typeAbonnementChoisi): self { $this->typeAbonnementChoisi = $typeAbonnementChoisi; return $this; }

    public function getMontantCalcule(): ?string { return $this->montantCalcule; }
    public function setMontantCalcule(?string $montantCalcule): self { $this->montantCalcule = $montantCalcule; return $this; }

    public function getDevise(): string { return $this->devise; }
    public function setDevise(string $devise): self { $this->devise = $devise; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }

    public function getBesoinsSpeciaux(): ?string { return $this->besoinsSpeciaux; }
    public function setBesoinsSpeciaux(?string $besoinsSpeciaux): self { $this->besoinsSpeciaux = $besoinsSpeciaux; return $this; }

    public function getMealOption(): ?string { return $this->mealOption; }
    public function setMealOption(?string $mealOption): self { $this->mealOption = $mealOption; return $this; }

    public function getPointsEarned(): int { return $this->pointsEarned; }
    public function setPointsEarned(int $pointsEarned): self { $this->pointsEarned = $pointsEarned; return $this; }

    public function getAbonnement(): ?Abonnement { return $this->abonnement; }
    public function setAbonnement(?Abonnement $abonnement): self { $this->abonnement = $abonnement; return $this; }

    public function getRestaurant(): ?Restaurant { return $this->restaurant; }
    public function setRestaurant(?Restaurant $restaurant): self { $this->restaurant = $restaurant; return $this; }

    public function getMenu(): ?Menu { return $this->menu; }
    public function setMenu(?Menu $menu): self { $this->menu = $menu; return $this; }

    public function getRaisonAnnulation(): ?string { return $this->raisonAnnulation; }
    public function setRaisonAnnulation(?string $raisonAnnulation): self { $this->raisonAnnulation = $raisonAnnulation; return $this; }
}
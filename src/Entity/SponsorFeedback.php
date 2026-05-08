<?php

namespace App\Entity;

use App\Repository\SponsorFeedbackRepository;
use App\Entity\Email;
use Doctrine\ORM\Mapping as ORM;

use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: SponsorFeedbackRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SponsorFeedback
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // type: 'feedback' or 'report'
    #[ORM\Column(length: 20)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $nom;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    #[ORM\Column(type: 'text')]
    private string $contenu;



    #[ORM\ManyToOne(targetEntity: Sponsor::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Sponsor $sponsor;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sentimentScore = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $sentimentLabel = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sentimentConfidence = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $analyzedAt = null;

    public function __construct()
    {
        $this->email = new Email();
    }

    #[ORM\PrePersist]
    public function ensureEmail(): void
    {
        if (!isset($this->email)) {
            $this->email = new Email();
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getEmail(): Email { return $this->email; }
    public function setEmail(Email $email): static { $this->email = $email; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }



    public function getSponsor(): Sponsor { return $this->sponsor; }
    public function setSponsor(Sponsor $sponsor): static { $this->sponsor = $sponsor; return $this; }

    public function getSentimentScore(): ?float { return $this->sentimentScore; }
    public function setSentimentScore(?float $sentimentScore): static { $this->sentimentScore = $sentimentScore; return $this; }

    public function getSentimentLabel(): ?string { return $this->sentimentLabel; }
    public function setSentimentLabel(?string $sentimentLabel): static { $this->sentimentLabel = $sentimentLabel; return $this; }

    public function getSentimentConfidence(): ?float { return $this->sentimentConfidence; }
    public function setSentimentConfidence(?float $sentimentConfidence): static { $this->sentimentConfidence = $sentimentConfidence; return $this; }

    public function getAnalyzedAt(): ?\DateTimeImmutable { return $this->analyzedAt; }
    protected function setAnalyzedAt(?\DateTimeImmutable $analyzedAt): static { $this->analyzedAt = $analyzedAt; return $this; }
}

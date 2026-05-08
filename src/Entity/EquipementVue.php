<?php

namespace App\Entity;

use App\Repository\EquipementVueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipementVueRepository::class)]
#[ORM\Table(name: 'equipement_vues')]
#[ORM\UniqueConstraint(name: 'uniq_equipement_user', columns: ['equipement_id', 'user_id'])]
class EquipementVue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(name: 'equipement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Equipements $equipement;

    #[ORM\Column(name: 'user_id', length: 255)]
    private string $userId;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $lastViewed;

    public function __construct()
    {
        $this->lastViewed = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getEquipement(): Equipements { return $this->equipement; }
    public function setEquipement(Equipements $equipement): static { $this->equipement = $equipement; return $this; }

    public function getUserId(): string { return $this->userId; }
    public function setUserId(string $userId): static { $this->userId = $userId; return $this; }

    public function getLastViewed(): \DateTimeInterface { return $this->lastViewed; }
    protected function setLastViewed(\DateTimeInterface $lastViewed): static
    {
        if ($lastViewed instanceof \DateTimeImmutable) {
            $lastViewed = \DateTime::createFromImmutable($lastViewed);
        }
        $this->lastViewed = $lastViewed;
        return $this;
    }
}

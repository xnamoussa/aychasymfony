<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MealTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(name: 'participation_id', referencedColumnName: 'id', nullable: false)]
    private Participation $participation;

    #[ORM\Column(type: "integer")]
    private int $userId;

    #[ORM\Column(type: "string", length: 255)]
    private string $qrCode;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $timeSlot;

    #[ORM\Column(type: "boolean")]
    private bool $used = false;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    // ================= CONSTRUCTEUR

    public function __construct()
    {
        $this->used = false;
    }

    public function initialiser(
        Participation $participation,
        int $userId,
        string $qrCode,
        \DateTimeInterface $timeSlot
    ): void {
        $this->participation = $participation;
        $this->userId = $userId;
        $this->qrCode = $qrCode;
        $this->timeSlot = $timeSlot;
        $this->used = false;
    }

    // ================= LOGIQUE MÉTIER

    public function utiliser(): void
    {
        $this->used = true;
        $this->usedAt = new \DateTime();
    }

    public function estUtilise(): bool
    {
        return $this->used;
    }

    public function estValide(): bool
    {
        return !$this->used && $this->timeSlot >= new \DateTime();
    }

    // ================= GETTERS / SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    public function setParticipation(Participation $participation): static
    {
        $this->participation = $participation;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getQrCode(): string
    {
        return $this->qrCode;
    }

    public function setQrCode(string $qrCode): static
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getTimeSlot(): \DateTimeInterface
    {
        return $this->timeSlot;
    }

    protected function setTimeSlot(\DateTimeInterface $timeSlot): static
    {
        $this->timeSlot = $timeSlot;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): static
    {
        $this->used = $used;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    protected function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            "MealTicket{id=%d, userId=%d, used=%s, timeSlot=%s}",
            $this->id,
            $this->userId,
            $this->used ? 'true' : 'false',
            $this->timeSlot->format('Y-m-d H:i')
        );
    }
}
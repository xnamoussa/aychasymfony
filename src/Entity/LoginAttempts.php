<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\LoginAttemptsRepository;

#[ORM\Entity(repositoryClass: LoginAttemptsRepository::class)]
#[ORM\Table(name: 'login_attempts')]
class LoginAttempts
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempt_count = 0;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $last_attempt_time = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $cooldown_until = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $banned_until = null;

    public function __construct()
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getAttemptCount(): int
    {
        return $this->attempt_count;
    }

    public function setAttemptCount(int $attempt_count): static
    {
        $this->attempt_count = $attempt_count;
        return $this;
    }

    public function getLastAttemptTime(): ?\DateTimeImmutable { return $this->last_attempt_time; }
    public function setLastAttemptTime(?\DateTimeImmutable $last_attempt_time): static { $this->last_attempt_time = $last_attempt_time; return $this; }

    public function getCooldownUntil(): ?\DateTimeImmutable { return $this->cooldown_until; }
    public function setCooldownUntil(?\DateTimeImmutable $cooldown_until): static { $this->cooldown_until = $cooldown_until; return $this; }

    public function getBannedUntil(): ?\DateTimeImmutable { return $this->banned_until; }
    public function setBannedUntil(?\DateTimeImmutable $banned_until): static { $this->banned_until = $banned_until; return $this; }
}
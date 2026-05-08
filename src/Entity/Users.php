<?php
// src/Entity/Users.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Equipements;
use App\Entity\Delivery;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UsersRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Email;
use App\Entity\Phone;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\Table(name: 'users')]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ["unsigned" => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(min: 3, minMessage: "Name must be at least 3 letters", max: 100)]
    private string $name;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    // No Assert on password — hashed value is stored, plain-text validation
    // is done in RegistrationFormType before the controller hashes it.
    #[ORM\Column(type: 'string', length: 255)]
    // #[\Symfony\Component\Serializer\Attribute\Ignore]
    private string $password;

    // BANNED is a valid role so the ban feature works without DB schema changes
    #[ORM\Column(type: 'string', length: 50)]
    private string $role = 'USER';

    #[ORM\Column(type: "string", length: 3, nullable: true)]
    #[Assert\Choice(choices: ["YES", "NO"], message: "Motorized must be YES or NO")]
    private ?string $motorized = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Embedded(class: Phone::class, columnPrefix: false)]
    private Phone $phone;
    
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $resetCode = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetCodeExpiresAt = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $persona = null;

    /** @var Collection<int, Equipements> */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Equipements::class)]
    private Collection $equipements;

    /** @var Collection<int, Delivery> */
    #[ORM\OneToMany(mappedBy: 'acheteur', targetEntity: Delivery::class)]
    private Collection $livraisons;

    public function __construct()
    {
        $this->equipements = new ArrayCollection();
        $this->livraisons = new ArrayCollection();
        $this->email = new Email();
        $this->phone = new Phone();
    }

    // =========================================================
    // UserInterface
    // =========================================================

    public function getUserIdentifier(): string
    {
        return $this->email->getValue();
    }

    public function getRoles(): array
    {
        return match($this->role) {
            'ADMIN'  => ['ROLE_ADMIN', 'ROLE_USER'],
            'BANNED' => ['ROLE_BANNED'],
            default  => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void {}

    // =========================================================
    // PasswordAuthenticatedUserInterface
    // =========================================================

    public function getPassword(): string
    {
        return $this->password;
    }

    // =========================================================
    // Getters & Setters
    // =========================================================

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getEmail(): Email { return $this->email; }
    public function setEmail(Email $email): static { $this->email = $email; return $this; }

    public function setPassword(#[ \SensitiveParameter] string $password): static { $this->password = $password; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getMotorized(): ?string { return $this->motorized; }
    public function setMotorized(?string $motorized): static { $this->motorized = $motorized; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getPhone(): Phone { return $this->phone; }
    public function setPhone(Phone $phone): static { $this->phone = $phone; return $this; }

    /** @return Collection<int, Equipements> */
    public function getEquipements(): Collection
    {
        return $this->equipements;
    }

    public function addEquipement(Equipements $equipement): static
    {
        if (!$this->equipements->contains($equipement)) {
            $this->equipements->add($equipement);
            $equipement->setOwner($this);
        }
        return $this;
    }

    public function removeEquipement(Equipements $equipement): static
    {
        if ($this->equipements->removeElement($equipement)) {
            if ($equipement->getOwner() === $this) {
                $equipement->setOwner(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Delivery> */
    public function getLivraisons(): Collection
    {
        return $this->livraisons;
    }

    public function addLivraison(Delivery $livraison): static
    {
        if (!$this->livraisons->contains($livraison)) {
            $this->livraisons->add($livraison);
            $livraison->setAcheteur($this);
        }
        return $this;
    }

    public function removeLivraison(Delivery $livraison): static
    {
        if ($this->livraisons->removeElement($livraison)) {
            if ($livraison->getAcheteur() === $this) {
                $livraison->setAcheteur(null);
            }
        }
        return $this;
    }

    public function getResetCode(): ?string
    {
        return $this->resetCode;
    }

    public function setResetCode(?string $resetCode): self
    {
        $this->resetCode = $resetCode;
        return $this;
    }

    public function getResetCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetCodeExpiresAt;
    }

    protected function setResetCodeExpiresAt(?\DateTimeImmutable $resetCodeExpiresAt): self
    {
        $this->resetCodeExpiresAt = $resetCodeExpiresAt;
        return $this;
    }

    public function getPersona(): ?string
    {
        return $this->persona;
    }

    public function setPersona(?string $persona): self
    {
        $this->persona = $persona;
        return $this;
    }
}
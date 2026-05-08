<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class SessionUser implements UserInterface
{
    private int $id;
    private string $identifier;
    /** @var list<string> */
    private array $roles;

    /** @param list<string> $roles */
    public function __construct(int $id, string $identifier, array $roles)
    {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->roles = $roles;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}

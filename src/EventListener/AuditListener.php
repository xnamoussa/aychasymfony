<?php

namespace App\EventListener;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class AuditListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->getUser();

        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
        if ($user && method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy($user);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->getUser();

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
        if ($user && method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        }
    }

    private function getUser(): ?Users
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof Users) {
            return null;
        }

        return $user;
    }
}

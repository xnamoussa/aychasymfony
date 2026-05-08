<?php
namespace App\Repository;

use App\Entity\Restauration;
use Doctrine\ORM\EntityManagerInterface;

class RestaurationRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Restauration::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Trouve un Restauration par son ID
     */
    public function find(int $id): ?Restauration
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne toutes les restaurations
     * @return array<Restauration>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne des restaurations selon des critères
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<Restauration>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Sauvegarde une Restauration
     */
    public function save(Restauration $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime une Restauration
     */
    public function remove(Restauration $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
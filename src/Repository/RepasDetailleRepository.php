<?php
namespace App\Repository;

use App\Entity\RepasDetaille;
use Doctrine\ORM\EntityManagerInterface;

class RepasDetailleRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = RepasDetaille::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Trouve un repas détaillé par son ID
     */
    public function find(int $id): ?RepasDetaille
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne tous les repas détaillés
     *
     * @return array<RepasDetaille>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<RepasDetaille>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Sauvegarde un repas détaillé
     */
    public function save(RepasDetaille $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un repas détaillé
     */
    public function remove(RepasDetaille $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
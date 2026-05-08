<?php
namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;

class RestaurantRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Restaurant::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Trouve un restaurant par son ID
     */
    public function find(int $id): ?Restaurant
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne tous les restaurants
     *
     * @return array<Restaurant>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne des restaurants selon des critères
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<Restaurant>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Retourne tous les restaurants actifs
     *
     * @return array<Restaurant>
     */
    public function findActifs(): array
    {
        return $this->em->getRepository($this->entityClass)->findBy(['actif' => true]);
    }

    /**
     * Sauvegarde un restaurant
     */
    public function save(Restaurant $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un restaurant
     */
    public function remove(Restaurant $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria): int
    {
        return $this->em->getRepository($this->entityClass)->count($criteria);
    }
}
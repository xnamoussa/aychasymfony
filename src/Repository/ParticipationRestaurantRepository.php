<?php
namespace App\Repository;

use App\Entity\ParticipationRestaurant;
use Doctrine\ORM\EntityManagerInterface;

class ParticipationRestaurantRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = ParticipationRestaurant::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retourne une participation restaurant par son ID
     */
    public function find(int $id): ?ParticipationRestaurant
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne toutes les participations restaurant
     * @return array<ParticipationRestaurant>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne des participations restaurant selon des critères
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<ParticipationRestaurant>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Sauvegarde une participation restaurant
     */
    public function save(ParticipationRestaurant $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime une participation restaurant
     */
    public function remove(ParticipationRestaurant $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
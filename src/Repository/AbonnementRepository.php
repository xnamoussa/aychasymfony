<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AbonnementRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Abonnement::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return array<Abonnement>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Trouve un Abonnement par son ID
     */
    public function find(int $id): ?Abonnement
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Récupère les abonnements selon le statut fourni
     * @return array<Abonnement>
     */
    public function findByStatut(string $statut): array
    {
        return $this->em->getRepository($this->entityClass)->findBy(['statut' => $statut]);
    }

    /**
     * Récupère tous les abonnements actifs
     * @return array<Abonnement>
     */
    public function findActifs(): array
    {
        return $this->findByStatut('ACTIF');
    }

    /**
     * Crée un QueryBuilder pour l'entité Abonnement
     */
    public function createQueryBuilder(string $alias): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias);
    }

    /**
     * Retourne les revenus cumulés par type d'abonnement pour les abonnements actifs
     * @return array<int, array<string, mixed>>
     */
    public function getRevenueStats(): array
    {
        return $this->em->createQueryBuilder()
            ->select('NEW App\Dto\StatsDto(a.type, SUM(a.prix))')
            ->from(Abonnement::class, 'a')
            ->where('a.statut = :statut')
            ->setParameter('statut', Abonnement::STATUT_ACTIF)
            ->groupBy('a.type')
            ->getQuery()
            ->getResult();
    }
    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria): int
    {
        return $this->em->getRepository($this->entityClass)->count($criteria);
    }
}
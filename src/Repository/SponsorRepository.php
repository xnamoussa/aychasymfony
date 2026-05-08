<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<Sponsor>
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($filters['search'])) {
            $qb->andWhere('s.nom LIKE :search OR s.email LIKE :search OR s.telephone LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $qb->andWhere('s.statut = :statut')
               ->setParameter('statut', (bool) $filters['statut']);
        }

        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('s.dateCreation >= :dateDebut')
               ->setParameter('dateDebut', new \DateTime($filters['dateDebut']));
        }

        if (!empty($filters['dateFin'])) {
            $qb->andWhere('s.dateCreation <= :dateFin')
               ->setParameter('dateFin', new \DateTime($filters['dateFin'] . ' 23:59:59'));
        }

        $sortField = in_array($filters['sort'] ?? '', ['nom', 'email', 'dateCreation', 'statut']) 
            ? $filters['sort'] : 'id';
        $sortDir = ($filters['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        $qb->orderBy('s.' . $sortField, $sortDir);

        return $qb->getQuery()->getResult();
    }
}
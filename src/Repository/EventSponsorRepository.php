<?php

namespace App\Repository;

use App\Entity\EventSponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventSponsor>
 */
class EventSponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventSponsor::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<EventSponsor>
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('es')
            ->join('es.sponsor', 's')
            ->join('es.event', 'e');

        if (!empty($filters['search'])) {
            $qb->andWhere('s.nom LIKE :search OR e.titre LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['niveau'])) {
            $qb->andWhere('es.niveau = :niveau')
               ->setParameter('niveau', $filters['niveau']);
        }

        if (!empty($filters['montantMin'])) {
            $qb->andWhere('es.montant >= :montantMin')
               ->setParameter('montantMin', $filters['montantMin']);
        }

        if (!empty($filters['montantMax'])) {
            $qb->andWhere('es.montant <= :montantMax')
               ->setParameter('montantMax', $filters['montantMax']);
        }

        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('es.dateAssociation >= :dateDebut')
               ->setParameter('dateDebut', new \DateTime($filters['dateDebut']));
        }

        if (!empty($filters['dateFin'])) {
            $qb->andWhere('es.dateAssociation <= :dateFin')
               ->setParameter('dateFin', new \DateTime($filters['dateFin'] . ' 23:59:59'));
        }

        $sortMap = [
            'sponsor' => 's.nom',
            'event'   => 'e.titre',
            'niveau'  => 'es.niveau',
            'montant' => 'es.montant',
            'date'    => 'es.dateAssociation',
        ];
        $sortField = $sortMap[$filters['sort'] ?? ''] ?? 'es.id';
        $sortDir = ($filters['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        $qb->orderBy($sortField, $sortDir);

        return $qb->getQuery()->getResult();
    }
}
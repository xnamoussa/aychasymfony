<?php

namespace App\Repository;

use App\Entity\Equipements;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipements>
 */
class EquipementsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipements::class);
    }

    /**
     * Liste boutique : tous les équipements, plus récents en premier.
     * NOTE: No LIMIT used here since the controller does in-memory filtering.
     * addSelect eagerly loads attributs in a single query — correct without setMaxResults.
     *
     * @return list<Equipements>
     */
    public function findAllOrderedByDateDesc(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.attributs', 'a')->addSelect('a')
            ->orderBy('e.dateAjout', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Paginated version using Doctrine Paginator to correctly handle
     * the OneToMany 'attributs' collection join with LIMIT/OFFSET.
     * Use this when you need server-side pagination.
     *
     * @return Paginator<Equipements>
     */
    public function findPaginated(int $page = 1, int $perPage = 20): Paginator
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.attributs', 'a')->addSelect('a')
            ->orderBy('e.dateAjout', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        // fetchJoinCollection=true ensures Paginator runs 2 queries
        // to correctly count entities (not SQL rows) when a collection join is present
        return new Paginator($qb->getQuery(), fetchJoinCollection: true);
    }
}


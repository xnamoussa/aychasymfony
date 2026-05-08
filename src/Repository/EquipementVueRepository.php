<?php

namespace App\Repository;

use App\Entity\Equipements;
use App\Entity\EquipementVue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipementVue>
 */
class EquipementVueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipementVue::class);
    }

    public function countForEquipement(Equipements $equipement): int
    {
        $dto = $this->createQueryBuilder('v')
            ->select("NEW App\Dto\StatsDto('count', COUNT(v.id))")
            ->andWhere('v.equipement = :e')
            ->setParameter('e', $equipement)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $dto ? (int) $dto->total : 0;
    }

    public function findOneByEquipementAndUser(Equipements $equipement, string $userId): ?EquipementVue
    {
        return $this->findOneBy(['equipement' => $equipement, 'userId' => $userId]);
    }
}

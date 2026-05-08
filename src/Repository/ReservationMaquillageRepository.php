<?php

namespace App\Repository;

use App\Entity\ReservationMaquillage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationMaquillage>
 */
class ReservationMaquillageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationMaquillage::class);
    }
}

<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }
    
    /**
     * @return array<Transaction>
     */
    public function findAllOrderedByDateDesc(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalValue(): float
    {
        $dto = $this->createQueryBuilder('t')
            ->select("NEW App\Dto\StatsDto('total', COALESCE(SUM(t.price), 0))")
            ->getQuery()
            ->getOneOrNullResult();
        
        return $dto ? (float) $dto->total : 0.0;
    }
}

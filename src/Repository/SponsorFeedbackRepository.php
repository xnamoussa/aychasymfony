<?php

namespace App\Repository;

use App\Entity\SponsorFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SponsorFeedback>
 */
class SponsorFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorFeedback::class);
    }

    /**
     * @return array<SponsorFeedback>
     */
    public function findAllFeedbacks(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.type = :type')
            ->setParameter('type', 'feedback')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<SponsorFeedback>
     */
    public function findAllReports(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.type = :type')
            ->setParameter('type', 'report')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

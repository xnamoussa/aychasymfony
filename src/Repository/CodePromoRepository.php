<?php
namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\ORM\EntityManagerInterface;

class CodePromoRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = CodePromo::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return array<CodePromo>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne tous les codes actifs et non expirés
     *
     * @return CodePromo[]
     */
    public function findAllActive(): array
    {
        return $this->em->getRepository($this->entityClass)
            ->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->andWhere('c.dateExpiration > :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un code promo par son code (en majuscules)
     */
    public function findOneByCodeUppercase(string $code): ?CodePromo
    {
        return $this->em->getRepository($this->entityClass)
            ->findOneBy(['code' => strtoupper($code)]);
    }

    /**
     * Méthodes de base que tu peux ajouter facilement
     */
    /**
     * @param array<string, mixed> $criteria
     * @return array<CodePromo>
     */
    public function findBy(array $criteria): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?CodePromo
    {
        return $this->em->getRepository($this->entityClass)->findOneBy($criteria);
    }

    public function find(int $id): ?CodePromo
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    public function save(CodePromo $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function remove(CodePromo $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
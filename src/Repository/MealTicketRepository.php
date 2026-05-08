<?php
namespace App\Repository;

use App\Entity\MealTicket;
use Doctrine\ORM\EntityManagerInterface;

class MealTicketRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = MealTicket::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retourne tous les tickets
     *
     * @return array<MealTicket>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne un ticket par son ID
     */
    public function find(int $id): ?MealTicket
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne tous les tickets actifs (statut VALIDE)
     *
     * @return array<MealTicket>
     */
    public function findActifs(): array
    {
        return $this->em->getRepository($this->entityClass)
            ->createQueryBuilder('m')
            ->andWhere('m.statut = :statut')
            ->setParameter('statut', 'VALIDE') // ou une constante StatutTicket::VALIDE si tu l’as définie
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un ticket par son code unique
     */
    public function findOneByQrCode(string $code): ?MealTicket
    {
        return $this->em->getRepository($this->entityClass)
            ->findOneBy(['qrCode' => $code]);
    }

    /**
     * Finds a single entity by a set of criteria.
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?MealTicket
    {
        return $this->em->getRepository($this->entityClass)->findOneBy($criteria, $orderBy);
    }

    /**
     * Sauvegarde un ticket
     */
    public function save(MealTicket $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un ticket
     */
    public function remove(MealTicket $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
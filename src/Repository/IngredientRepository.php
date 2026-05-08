<?php
namespace App\Repository;

use App\Entity\Ingredient;
use Doctrine\ORM\EntityManagerInterface;

class IngredientRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Ingredient::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retourne tous les ingrédients
     *
     * @return array<Ingredient>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne un ingrédient par son ID
     */
    public function find(int $id): ?Ingredient
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Proxies findOneBy to the actual repository
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?Ingredient
    {
        return $this->em->getRepository($this->entityClass)->findOneBy($criteria, $orderBy);
    }

    /**
     * Retourne les ingrédients actifs
     *
     * @return array<Ingredient>
     */
    public function findActifs(): array
    {
        return $this->em->getRepository($this->entityClass)
            ->createQueryBuilder('i')
            ->andWhere('i.actif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Sauvegarde un ingrédient
     */
    public function save(Ingredient $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un ingrédient
     */
    public function remove(Ingredient $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
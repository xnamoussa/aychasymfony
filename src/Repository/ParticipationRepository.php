<?php
namespace App\Repository;

use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ParticipationRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Participation::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retourne une participation par son ID
     */
    public function find(int $id): ?Participation
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne toutes les participations
     *
     * @return array<Participation>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne les participations correspondant à des critères
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<Participation>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Retourne les participations correspondant à un statut donné
     *
     * @param string $statut
     * @return array<Participation>
     */
    public function findByStatut(string $statut): array
    {
        return $this->em->getRepository($this->entityClass)
            ->findBy(['statut' => $statut]);
    }

    /**
     * Calcule le nombre de places disponibles pour un événement
     */
    public function getPlacesDisponibles(int $evenementId): int
    {
        // On récupère le nombre total de participants confirmés
        $qb = $this->createQueryBuilder('p')
            ->select("NEW App\Dto\StatsDto('total', COALESCE(SUM(p.totalParticipants), 0))")
            ->where('p.evenementId = :evenementId')
            ->andWhere('p.statut = :statut')
            ->setParameter('evenementId', $evenementId)
            ->setParameter('statut', Participation::STATUT_CONFIRME);

        $dto = $qb->getQuery()->getOneOrNullResult();
        $totalParticipations = $dto ? (int) $dto->total : 0;

        // Par défaut, on retourne 100 s'il n'y a pas de limite connue, ou on la récupère de l'entité Evénement
        $capaciteMax = 100; // placeholder pour la capacité max
        $placesRestantes = $capaciteMax - $totalParticipations;

        return max(0, $placesRestantes);
    }

    /**
     * Crée un QueryBuilder pour l'entité Participation
     */
    public function createQueryBuilder(string $alias): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityClass, $alias);
    }

    public function getTotalParticipantsCount(): int
    {
        $dto = $this->createQueryBuilder('p')
            ->select("NEW App\Dto\StatsDto('total', COALESCE(SUM(p.totalParticipants), 0))")
            ->where('p.statut = :statut')
            ->setParameter('statut', Participation::STATUT_CONFIRME)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $dto ? (int) $dto->total : 0;
    }

    public function getTotalRevenue(): float
    {
        $dto = $this->createQueryBuilder('p')
            ->select("NEW App\Dto\StatsDto('total', COALESCE(SUM(p.montantCalcule), 0))")
            ->where('p.statut = :statut')
            ->setParameter('statut', Participation::STATUT_CONFIRME)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $dto ? (float) $dto->total : 0.0;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria): int
    {
        return $this->em->getRepository($this->entityClass)->count($criteria);
    }

    /**
     * Sauvegarde une participation
     */
    public function save(Participation $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime une participation
     */
    public function remove(Participation $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
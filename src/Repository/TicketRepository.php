<?php
namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;

class TicketRepository
{
    private EntityManagerInterface $em;
    private string $entityClass = Ticket::class;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Trouve un Ticket par son ID
     */
    public function find(int $id): ?Ticket
    {
        return $this->em->getRepository($this->entityClass)->find($id);
    }

    /**
     * Retourne tous les tickets
     *
     * @return array<Ticket>
     */
    public function findAll(): array
    {
        return $this->em->getRepository($this->entityClass)->findAll();
    }

    /**
     * Retourne des tickets selon des critères
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return array<Ticket>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->em->getRepository($this->entityClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Retourne tous les tickets valides
     *
     * @return array<Ticket>
     */
    public function findValides(): array
    {
        return $this->findBy(['statut' => 'VALIDE']);
    }

    /**
     * Sauvegarde un Ticket
     */
    public function save(Ticket $ticket, bool $flush = true): void
    {
        $this->em->persist($ticket);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Supprime un Ticket
     */
    public function remove(Ticket $ticket, bool $flush = true): void
    {
        $this->em->remove($ticket);
        if ($flush) {
            $this->em->flush();
        }
    }
}
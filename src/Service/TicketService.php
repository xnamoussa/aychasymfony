<?php
namespace App\Service;

use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ticket;

class TicketService extends GenericService {
    private TicketRepository $repo;

    public function __construct(EntityManagerInterface $em, TicketRepository $repo) {
        parent::__construct($em);
        $this->repo = $repo;
    }

    /** @return Ticket[] */
    public function findAll(): array {
        return $this->repo->findAll();
    }

    public function find(int $id): ?Ticket {
        return $this->repo->find($id);
    }

    /**
     * Récupère les tickets d'un utilisateur par son ID
     *
     * @param int $userId
     * @return Ticket[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->repo->findBy(['userId' => $userId]);
    }
}

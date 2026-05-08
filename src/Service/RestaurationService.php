<?php
namespace App\Service;
use App\Repository\RestaurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Restauration;
class RestaurationService extends GenericService {
    private RestaurationRepository $repo;
    public function __construct(EntityManagerInterface $em, RestaurationRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return Restauration[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?Restauration { return $this->repo->find($id); }
}

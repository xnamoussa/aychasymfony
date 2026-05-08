<?php
namespace App\Service;
use App\Repository\RepasDetailleRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RepasDetaille;
class RepasDetailleService extends GenericService {
    private RepasDetailleRepository $repo;
    public function __construct(EntityManagerInterface $em, RepasDetailleRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return RepasDetaille[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?RepasDetaille { return $this->repo->find($id); }
}

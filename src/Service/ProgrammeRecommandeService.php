<?php
namespace App\Service;
use App\Repository\ProgrammeRecommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ProgrammeRecommande;
class ProgrammeRecommandeService extends GenericService {
    private ProgrammeRecommandeRepository $repo;
    public function __construct(EntityManagerInterface $em, ProgrammeRecommandeRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return ProgrammeRecommande[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?ProgrammeRecommande { return $this->repo->find($id); }
}

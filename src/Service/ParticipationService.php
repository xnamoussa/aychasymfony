<?php
namespace App\Service;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
class ParticipationService extends GenericService {
    private ParticipationRepository $repo;
    public function __construct(EntityManagerInterface $em, ParticipationRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    
    /** @return array<\App\Entity\Participation> */
    public function findAll(): array { return $this->repo->findAll(); }
    
    public function find(int $id): ?\App\Entity\Participation { return $this->repo->find($id); }
    
    public function create(\App\Entity\Participation $entity): \App\Entity\Participation {
        $this->enrichirTarification($entity);
        $this->save($entity);
        return $entity;
    }
    
    private function enrichirTarification(\App\Entity\Participation $p): void {
        if (!$p->getNbAdultes()) $p->setNbAdultes(1);
        $p->setTotalParticipants($p->getNbAdultes() + $p->getNbEnfants());
        $montant = ($p->getNbAdultes() * 25.00) + ($p->getNbEnfants() * 15.00);
        if ($p->getMealOption() === 'EXTRA') $montant += 10;
        $p->setMontantCalcule((string)$montant);
        if (!$p->getDevise()) $p->setDevise('EUR');
    }
}

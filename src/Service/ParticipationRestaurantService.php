<?php
namespace App\Service;
use App\Repository\ParticipationRestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ParticipationRestaurant;
class ParticipationRestaurantService extends GenericService {
    private ParticipationRestaurantRepository $repo;
    public function __construct(EntityManagerInterface $em, ParticipationRestaurantRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return ParticipationRestaurant[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?ParticipationRestaurant { return $this->repo->find($id); }
}

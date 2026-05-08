<?php
namespace App\Service;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ingredient;
class IngredientService extends GenericService {
    private IngredientRepository $repo;
    public function __construct(EntityManagerInterface $em, IngredientRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return Ingredient[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?Ingredient { return $this->repo->find($id); }
}

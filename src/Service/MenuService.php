<?php
namespace App\Service;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Menu;
class MenuService extends GenericService {
    private MenuRepository $repo;
    public function __construct(EntityManagerInterface $em, MenuRepository $repo) { parent::__construct($em); $this->repo = $repo; }
    /** @return Menu[] */
    public function findAll(): array { return $this->repo->findAll(); }
    public function find(int $id): ?Menu { return $this->repo->find($id); }
}

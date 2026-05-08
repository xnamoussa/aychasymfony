<?php

namespace App\Service;

use App\Entity\Equipements;
use App\Entity\EquipementVue;
use App\Repository\EquipementVueRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour le comptage des vues par utilisateur.
 */
class EquipementsVueService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EquipementVueRepository $vueRepository,
    ) {
    }

    /**
     * Enregistre une vue unique par couple (équipement, userId). Incrémente nombre_vues si nouvelle vue.
     */
    public function registerView(Equipements $equipement, string $userId): bool
    {
        $userId = trim($userId);
        if ($userId === '') {
            return false;
        }

        $existing = $this->vueRepository->findOneByEquipementAndUser($equipement, $userId);
        if ($existing !== null) {
            $existing->setLastViewed(new \DateTime());
            $this->em->flush();

            return false;
        }

        $vue = new EquipementVue();
        $vue->setEquipement($equipement);
        $vue->setUserId($userId);
        $this->em->persist($vue);

        $equipement->setNombreVues($equipement->getNombreVues() + 1);
        $this->em->flush();

        return true;
    }

    public function getViewsCount(Equipements $equipement): int
    {
        return $this->vueRepository->countForEquipement($equipement);
    }
}

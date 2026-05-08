<?php

namespace App\Controller;

use App\Entity\CompositionMenu;
use App\Entity\RepasDetaille;
use App\Repository\CompositionMenuRepository;
use App\Repository\RepasDetailleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/menu-planner')]
class MenuPlannerController extends AbstractController
{
    #[Route('/', name: 'app_menu_planner_index', methods: ['GET'])]
    public function index(RepasDetailleRepository $repasRepository): Response
    {
        $plats = $repasRepository->findBy(['actif' => true]);

        return $this->render('menu_planner/index.html.twig', [
            'plats' => $plats
        ]);
    }

    #[Route('/api/load', name: 'app_menu_planner_load', methods: ['GET'])]
    public function apiLoad(Request $request, EntityManagerInterface $em): Response
    {
        $startDateStr = $request->query->get('start');
        $endDateStr = $request->query->get('end');

        if (!$startDateStr || !$endDateStr) {
            return $this->json(['error' => 'Dates manquantes'], 400);
        }

        $startDate = new \DateTime($startDateStr);
        $endDate = new \DateTime($endDateStr);

        $compositions = $em->getRepository(CompositionMenu::class)->createQueryBuilder('c')
            ->where('c.date >= :start')
            ->andWhere('c.date <= :end')
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($compositions as $comp) {
            if ($comp->getRepas()) {
                $data[] = [
                    'id' => $comp->getId(),
                    'date' => $comp->getDate()->format('Y-m-d'),
                    'typeRepas' => $comp->getTypeRepas(), // EX: 'PETIT_DEJEUNER', 'DEJEUNER', 'DINER'
                    'repas_id' => $comp->getRepas()->getId(),
                    'repas_nom' => $comp->getRepas()->getNom(),
                    'repas_prix' => $comp->getRepas()->getPrix(),
                    'repas_image' => $comp->getRepas()->getImageUrl(),
                ];
            }
        }

        return $this->json($data);
    }

    #[Route('/api/save', name: 'app_menu_planner_save', methods: ['POST'])]
    public function apiSave(Request $request, EntityManagerInterface $em, RepasDetailleRepository $repasRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        $dateStr = $data['date'] ?? null;
        $typeRepas = $data['typeRepas'] ?? null;
        $repasId = $data['repas_id'] ?? null;

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if (!$dateStr || !$typeRepas || !$repasId) {
            return $this->json(['error' => 'Données incomplètes'], 400);
        }

        $date = new \DateTime($dateStr);
        $repas = $repasRepository->find($repasId);

        if (!$repas) {
            return $this->json(['error' => 'Plat introuvable'], 404);
        }

        // On remplace si une compo existe déjà pour ce slot
        $existing = $em->getRepository(CompositionMenu::class)->findOneBy([
            'date' => $date,
            'typeRepas' => $typeRepas
        ]);

        if ($existing) {
            $comp = $existing;
        } else {
            $comp = new CompositionMenu();
            $comp->setDate($date);
            $comp->setTypeRepas($typeRepas);
        }

        $comp->setRepas($repas);
        $em->persist($comp);
        $em->flush();

        return $this->json(['success' => true, 'id' => $comp->getId(), 'repas_nom' => $repas->getNom()]);
    }

    #[Route('/api/remove', name: 'app_menu_planner_remove', methods: ['POST'])]
    public function apiRemove(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if (!$id) {
            return $this->json(['error' => 'ID manquant'], 400);
        }

        $comp = $em->getRepository(CompositionMenu::class)->find($id);
        if ($comp) {
            $em->remove($comp);
            $em->flush();
        }

        return $this->json(['success' => true]);
    }
}

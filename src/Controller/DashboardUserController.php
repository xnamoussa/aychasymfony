<?php

namespace App\Controller;

use App\Repository\FavoriRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardUserController extends AbstractController
{
    private FavoriRepository $favoriRepo;
    private ParticipationRepository $participationRepo;
    private \App\Service\UserPersonaClusteringService $clusteringService;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;

    public function __construct(
        FavoriRepository $favoriRepo, 
        ParticipationRepository $participationRepo,
        \App\Service\UserPersonaClusteringService $clusteringService,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {
        $this->favoriRepo = $favoriRepo;
        $this->participationRepo = $participationRepo;
        $this->clusteringService = $clusteringService;
        $this->entityManager = $entityManager;
    }

    #[Route('/dashboard/user', name: 'app_user_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }

        $userId = method_exists($user, 'getId') ? $user->getId() : 0;
        $favoris = $this->favoriRepo->findByUser($userId);
        
        $restaurantsFavoris = [];
        $repasFavoris = [];

        foreach ($favoris as $f) {
            if ($f->getRestaurant()) {
                $restaurantsFavoris[] = $f->getRestaurant();
            }
            if ($f->getRepasDetaille()) {
                $repasFavoris[] = $f->getRepasDetaille();
            }
        }

        // Fetch recent participations for the user
        $participations = $this->participationRepo->findBy(['userId' => $userId], ['dateInscription' => 'DESC'], 5);

        // --- USER PERSONA AI CLUSTERING ---
        $behaviorData = $this->clusteringService->analyzeUserBehavior($user);
        $scores = $this->clusteringService->calculateBehaviorScores($behaviorData);
        $persona = $this->clusteringService->detectPersona($scores);
        
        // Update user persona in DB (safe update)
        if ($user->getPersona() !== $persona) {
            $user->setPersona($persona);
            $this->entityManager->flush();
        }
        
        $dashboardPrefs = $this->clusteringService->generateDashboardPreferences($persona);
        $aiRecs = $this->clusteringService->generateTargetedRecommendations($persona);
        // ---------------------------------

        return $this->render('dashboard/user.html.twig', [
            'user' => $user,
            'restaurantsFavoris' => $restaurantsFavoris,
            'repasFavoris' => $repasFavoris,
            'participations' => $participations,
            'persona' => $persona,
            'dashboardPrefs' => $dashboardPrefs,
            'aiRecs' => $aiRecs,
        ]);
    }
}

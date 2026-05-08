<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Repository\ParticipationRepository;
use App\Repository\RestaurantRepository;
use App\Repository\UsersRepository;
use App\Entity\Participation;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        ParticipationRepository $pr, 
        RestaurantRepository $rr, 
        UsersRepository $ur,
        \App\Repository\AbonnementRepository $ar, 
        \App\Repository\FavoriRepository $fr,
        \App\Repository\EquipementsRepository $eqr,
        \App\Repository\TransactionRepository $tr
    ): Response
    {
        // Calculate platform stats
        $totalParticipants = $pr->getTotalParticipantsCount();
        $totalRevenue = $pr->getTotalRevenue();
        
        $totalAdmins = $ur->count(['role' => 'ADMIN']);
        $totalUsers = $ur->count(['role' => 'USER']);
        $totalBanned = $ur->count(['role' => 'BANNED']);
        
        $totalRestaurants = $rr->count([]);

        // Advanced Stats for Google Charts
        $revenueStats = $ar->getRevenueStats();
        $popularityStats = $fr->getPopularityStats();
        
        // Participation timeline (grouped by month)
        $timelineRaw = $pr->createQueryBuilder('p')
            ->select("NEW App\Dto\StatsDto(SUBSTRING(p.dateInscription, 1, 7), COUNT(p.id))")
            ->addSelect("SUBSTRING(p.dateInscription, 1, 7) AS HIDDEN month")
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        $recentParticipations = $pr->findBy([], ['id' => 'DESC'], 5);
        $recentUsers = $ur->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'totalParticipants' => $totalParticipants,
            'totalRestaurants' => $totalRestaurants,
            'totalRevenue' => $totalRevenue,
            'totalEquipements' => $eqr->count([]),
            'totalTransactionValue' => $tr->getTotalValue(),
            'totalAdmins' => $totalAdmins,
            'totalUsers' => $totalUsers,
            'totalBanned' => $totalBanned,
            'revenueStats' => $revenueStats,
            'popularityStats' => $popularityStats,
            'participationTimeline' => $timelineRaw,
            'recentParticipations' => $recentParticipations,
            'recentUsers' => $recentUsers
        ]);
    }
}

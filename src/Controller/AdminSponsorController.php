<?php

namespace App\Controller;

use App\Entity\SponsorFeedback;
use App\Repository\SponsorRepository;
use App\Repository\EventSponsorRepository;
use App\Repository\SponsorFeedbackRepository;
use App\Service\SentimentAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin')]
class AdminSponsorController extends AbstractController
{
    #[Route('/sponsors-dashboard', name: 'admin_sponsor_dashboard')]
    public function sponsorDashboard(
        SponsorRepository $sponsorRepository,
        EventSponsorRepository $eventSponsorRepository,
        HttpClientInterface $httpClient
    ): Response {
        $totalSponsors     = $sponsorRepository->count([]);
        $sponsorsActifs    = $sponsorRepository->count(['statut' => true]);
        $totalAssociations = $eventSponsorRepository->count([]);

        $allEventSponsors = $eventSponsorRepository->findAll();
        $montantTotal = 0;
        foreach ($allEventSponsors as $es) {
            $montantTotal += $es->getMontant();
        }

        $montantParEvenement = [];
        foreach ($allEventSponsors as $es) {
            try {
                $titre = $es->getEvent()->getTitre();
                if (!isset($montantParEvenement[$titre])) {
                    $montantParEvenement[$titre] = 0;
                }
                $montantParEvenement[$titre] += $es->getMontant();
            } catch (\Exception $e) {
                continue;
            }
        }
        arsort($montantParEvenement);

        $montantParNiveau = ['GOLD' => 0, 'SILVER' => 0, 'BRONZE' => 0, 'PARTENAIRE' => 0];
        foreach ($allEventSponsors as $es) {
            $niveau = $es->getNiveau();
            if (isset($montantParNiveau[$niveau])) {
                $montantParNiveau[$niveau] += $es->getMontant();
            }
        }

        $rates = [];
        try {
            $response = $httpClient->request('GET', 'https://open.er-api.com/v6/latest/TND', [
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray(false);
                $rates = $data['rates'] ?? [];
            }
        } catch (\Throwable $e) {
            // fallback: keep rates empty
        }

        return $this->render('admin/sponsor_dashboard.html.twig', [
            'totalSponsors'      => $totalSponsors,
            'sponsorsActifs'     => $sponsorsActifs,
            'totalAssociations'  => $totalAssociations,
            'montantTotal'       => $montantTotal,
            'montantParEvenement'=> $montantParEvenement,
            'montantParNiveau'   => $montantParNiveau,
            'rates'              => $rates,
            'baseCurrency'       => 'TND',
        ]);
    }

    #[Route('/sponsor-reports', name: 'admin_sponsor_reports')]
    public function sponsorReports(SponsorFeedbackRepository $feedbackRepository): Response
    {
        return $this->render('admin/sponsor_reports.html.twig', [
            'reports'   => $feedbackRepository->findAllReports(),
            'feedbacks' => $feedbackRepository->findAllFeedbacks(),
        ]);
    }

    #[Route('/sponsor-feedback', name: 'admin_sponsor_feedback')]
    public function sponsorFeedback(SponsorFeedbackRepository $repo, SentimentAnalysisService $sentimentService): Response
    {
        $entries = $repo->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculer le score de satisfaction global
        $analyzedEntries = array_filter($entries, fn($entry) => $entry->getSentimentScore() !== null);
        $globalScore = 0.0;
        $globalSatisfactionPercentage = 0;

        if (!empty($analyzedEntries)) {
            $totalScore = 0.0;
            foreach ($analyzedEntries as $entry) {
                $totalScore += $entry->getSentimentScore();
            }
            $globalScore = $totalScore / count($analyzedEntries);
            $globalSatisfactionPercentage = $sentimentService->getSatisfactionPercentage($globalScore);
        }

        return $this->render('admin/sponsor_feedback_admin.html.twig', [
            'entries' => $entries,
            'sentiment_service' => $sentimentService,
            'global_score' => $globalScore,
            'global_satisfaction' => $globalSatisfactionPercentage,
            'analyzed_count' => count($analyzedEntries),
        ]);
    }

    #[Route('/sponsor-feedback/analyze-batch', name: 'admin_analyze_batch_feedback', methods: ['POST'])]
    public function analyzeBatchFeedback(
        SponsorFeedbackRepository $repo,
        SentimentAnalysisService $sentimentService,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('analyze_batch', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_sponsor_feedback');
        }

        try {
            // Récupérer tous les feedbacks non analysés
            $feedbacks = $repo->createQueryBuilder('f')
                ->where('f.sentimentScore IS NULL')
                ->getQuery()
                ->getResult();

            $analyzed = 0;
            foreach ($feedbacks as $feedback) {
                try {
                    $result = $sentimentService->analyze($feedback->getContenu());

                    $feedback->setSentimentScore($result['score']);
                    $feedback->setSentimentLabel($result['label']);
                    $feedback->setSentimentConfidence($result['confidence'] ?? 0.0);
                    $feedback->setAnalyzedAt(new \DateTime());

                    $analyzed++;

                    // Petit délai pour éviter de surcharger l'API
                    usleep(100000); // 0.1 seconde

                } catch (\Exception $e) {
                    continue;
                }
            }

            $em->flush();
            $this->addFlash('success', sprintf('%d feedbacks analysés avec succès', $analyzed));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'analyse en batch : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_sponsor_feedback');
    }

    #[Route('/sponsor-feedback/{id}', name: 'admin_delete_feedback', methods: ['POST'])]
    public function deleteFeedback(SponsorFeedback $feedback, EntityManagerInterface $em, Request $request): Response
    {
        // Vérifier le token CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('delete' . $feedback->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_sponsor_feedback');
        }

        $em->remove($feedback);
        $em->flush();

        $this->addFlash('success', 'Feedback/Report supprimé avec succès');

        return $this->redirectToRoute('admin_sponsor_feedback');
    }

    #[Route('/sponsor-feedback/analyze/{id}', name: 'admin_analyze_feedback', methods: ['POST'])]
    public function analyzeFeedback(
        SponsorFeedback $feedback,
        SentimentAnalysisService $sentimentService,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Vérifier le token CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('analyze' . $feedback->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_sponsor_feedback');
        }

        try {
            // Analyser le sentiment du contenu
            $result = $sentimentService->analyze($feedback->getContenu());

            // Mettre à jour l'entité avec les résultats
            $feedback->setSentimentScore($result['score']);
            $feedback->setSentimentLabel($result['label']);
            $feedback->setSentimentConfidence($result['confidence'] ?? 0.0);
            $feedback->setAnalyzedAt(new \DateTime());

            $em->flush();

            $this->addFlash('success', sprintf(
                'Analyse terminée : %s (%d%% de confiance)',
                ucfirst($result['label']),
                round(($result['confidence'] ?? 0) * 100)
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'analyse : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_sponsor_feedback');
    }

}

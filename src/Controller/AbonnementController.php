<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;

#[Route('/abonnements')]
#[IsGranted('ROLE_USER')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AbonnementRepository $repository,
        private \App\Service\SmsService $smsService,
        private \App\Service\BadgeService $badgeService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — tableau de bord des souscriptions de l'utilisateur
    // Admin → toutes les souscriptions
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('', name: 'app_abonnement_index', methods: ['GET'])]
    public function index(): Response
    {
        $qb = $this->repository->createQueryBuilder('a')
            ->andWhere('a.isTemplate = :t')
            ->setParameter('t', false);

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $uid = method_exists($user, 'getId') ? $user->getId() : 0;
            $qb->andWhere('a.userId = :uid')
               ->setParameter('uid', $uid);
        }

        return $this->render('abonnement/index.html.twig', [
            'items' => $qb->getQuery()->getResult(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATALOGUE DES PLANS — visible par l'utilisateur pour souscrire
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/plans', name: 'app_abonnement_plans', methods: ['GET'])]
    public function catalogue(): Response
    {
        $plans = $this->repository->createQueryBuilder('a')
            ->andWhere('a.isTemplate = :t')
            ->setParameter('t', true)
            ->orderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();

        // Vérifier si l'utilisateur a déjà un abonnement actif
        $userAbonnements = [];
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            $subs = $this->repository->createQueryBuilder('a')
                ->andWhere('a.isTemplate = :f')
                ->andWhere('a.userId = :uid')
                ->setParameter('f', false)
                ->setParameter('uid', $userId)
                ->getQuery()
                ->getResult();
            foreach ($subs as $sub) {
                $userAbonnements[$sub->getPlanSourceId()] = $sub;
            }
        }

        return $this->render('abonnement/plans_catalogue.html.twig', [
            'plans'           => $plans,
            'userAbonnements' => $userAbonnements,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SOUSCRIRE À UN PLAN — POST, utilisateur seulement
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/souscrire/{planId}', name: 'app_abonnement_souscrire', methods: ['POST'])]
    public function souscrire(int $planId, Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', '⛔ Les administrateurs ne peuvent pas souscrire à un abonnement.');
            return $this->redirectToRoute('app_abonnement_plans');
        }

        if (!$this->isCsrfTokenValid('souscrire_' . $planId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_abonnement_plans');
        }

        $plan = $this->repository->find($planId);
        if (!$plan || !$plan->isTemplate()) {
            $this->addFlash('error', 'Plan introuvable.');
            return $this->redirectToRoute('app_abonnement_plans');
        }

        $user   = $this->getUser();
        $userId = method_exists($user, 'getId') ? $user->getId() : 0;

        // Vérifier si l'utilisateur a déjà souscrit à ce plan
        $qb = $this->repository->createQueryBuilder('a')
            ->andWhere('a.isTemplate = :f')
            ->andWhere('a.userId = :uid')
            ->andWhere('a.planSourceId = :planId')
            ->setParameter('f', false)
            ->setParameter('uid', $userId)
            ->setParameter('planId', $planId)
            ->setMaxResults(1);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb, true);
        $existing = null;
        foreach ($paginator as $item) {
            $existing = $item;
            break;
        }

        if ($existing) {
            $this->addFlash('warning', '⚠️ Vous avez déjà souscrit à ce plan.');
            return $this->redirectToRoute('app_abonnement_plans');
        }

        // Créer la souscription à partir du plan
        $abonnement = new Abonnement();
        $abonnement->setUserId($userId);
        $abonnement->setUserName($user ? $user->getUserIdentifier() : (string)$userId);
        $abonnement->setNom($plan->getNom());
        $abonnement->setType($plan->getType());
        $abonnement->setPrix($plan->getPrix());
        $abonnement->setRestrictionType($plan->getRestrictionType());
        $abonnement->setEvenementId($plan->getEvenementId());
        $abonnement->setAutoRenew($plan->isAutoRenew());
        $abonnement->setIsTemplate(false);
        $abonnement->setPlanSourceId($planId);
        $abonnement->setStatut(Abonnement::STATUT_ATTENTE);
        $abonnement->setDateDebut(new \DateTime());

        // Calculer la date de fin automatiquement
        $abonnement->initialiser();

        $this->em->persist($abonnement);
        $this->em->flush();

        // ─────────────────────────────────────────────────────────────────────────
        // INITIALISER STRIPE CHECKOUT
        // ─────────────────────────────────────────────────────────────────────────
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $checkout_session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Abonnement : ' . $plan->getNom(),
                        'description' => 'Facturation de souscription - Lamma Expédition',
                    ],
                    'unit_amount' => (int) ((float)$plan->getPrix() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('app_abonnement_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $urlGenerator->generate('app_abonnement_payment_cancel', ['id' => $abonnement->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'abonnement_id' => (string) $abonnement->getId(),
            ],
        ]);

        return $this->redirect($checkout_session->url, 303);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAIEMENT SUCCES (STRIPE CALLBACK)
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/paiement/success', name: 'app_abonnement_payment_success', methods: ['GET'])]
    public function paymentSuccess(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            return $this->redirectToRoute('app_abonnement_plans');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        try {
            $session = Session::retrieve($sessionId);
            if ($session->payment_status !== 'paid') {
                $this->addFlash('error', 'Le paiement n\'a pas été validé.');
                return $this->redirectToRoute('app_abonnement_plans');
            }

            $abonnementId = $session->metadata->abonnement_id ?? null;
            if (!$abonnementId) {
                throw new \Exception('ID d\'abonnement manquant dans les métadonnées Stripe.');
            }

            $abonnement = $this->repository->find($abonnementId);
            if (!$abonnement) {
                throw $this->createNotFoundException('Abonnement introuvable.');
            }

            // Seulement activer si ce n'est pas déjà fait
            if ($abonnement->getStatut() === Abonnement::STATUT_ATTENTE) {
                $abonnement->setStatut(Abonnement::STATUT_ACTIF);
                
                // Générer un ticket
                $ticket = new \App\Entity\Ticket();
                $ticket->setAbonnementId($abonnement->getId());
                $ticket->setUserId($abonnement->getUserId());
                $ticket->setType('TICKET');
                $ticket->setQrCode('TICKET-' . uniqid());
                
                $this->em->persist($ticket);
                $this->em->flush();

                // Notification SMS
                try {
                    $this->smsService->sendWelcomeSms("+21629051913"); // Vous pouvez adapter avec le vrai numéro récupéré
                } catch (\Exception $e) { }

                $this->addFlash('success', '💳 Paiement réussi ! Votre abonnement est maintenant Actif et votre facture a été générée.');
            }

            return $this->redirectToRoute('app_abonnement_show', ['id' => $abonnement->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la vérification du paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_abonnement_plans');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAIEMENT ANNULE (STRIPE CALLBACK)
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/paiement/cancel/{id}', name: 'app_abonnement_payment_cancel', methods: ['GET'])]
    public function paymentCancel(int $id): Response
    {
        $abonnement = $this->repository->find($id);
        if ($abonnement && $abonnement->getStatut() === Abonnement::STATUT_ATTENTE) {
            // Option 1 : Supprimer l'abonnement en attente pour nettoyer la bdd
            $this->em->remove($abonnement);
            $this->em->flush();
        }

        $this->addFlash('warning', 'Paiement annulé. Vous pouvez réessayer quand vous serez prêt.');
        return $this->redirectToRoute('app_abonnement_plans');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FACTURE PDF
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/{id}/facture', name: 'app_abonnement_facture', methods: ['GET'])]
    public function downloadFacture(int $id): Response
    {
        $abonnement = $this->repository->find($id);

        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement non trouvé.');
        }

        // Ownership check
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($abonnement->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Accès refusé à la facture.');
            }
        }

        if ($abonnement->getStatut() !== Abonnement::STATUT_ACTIF) {
            $this->addFlash('error', 'La facture n\'est disponible que pour les abonnements payés et actifs.');
            return $this->redirectToRoute('app_abonnement_show', ['id' => $id]);
        }

        $participantName = $user ? $user->getUserIdentifier() : 'Client';
        $pdfContent = $this->badgeService->generateFacturePdf($abonnement, $participantName);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facture-lamma-' . $id . '.pdf"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Gestion des plans d'abonnement (templates)
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/plans/gerer', name: 'app_abonnement_plans_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function plansAdmin(): Response
    {
        $plans = $this->repository->createQueryBuilder('a')
            ->andWhere('a.isTemplate = :t')
            ->setParameter('t', true)
            ->orderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('abonnement/plans_admin.html.twig', [
            'plans' => $plans,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Créer un nouveau plan
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/plans/nouveau', name: 'app_abonnement_plan_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function nouveauPlan(Request $request): Response
    {
        $plan = new Abonnement();
        $plan->setIsTemplate(true);
        $plan->setUserId(0);
        $plan->setStatut(Abonnement::STATUT_ACTIF);

        $form = $this->createForm(\App\Form\AbonnementPlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($plan);
            $this->em->flush();
            $this->addFlash('success', '✅ Plan "' . $plan->getNom() . '" créé avec succès.');
            return $this->redirectToRoute('app_abonnement_plans_admin');
        }

        return $this->render('abonnement/plan_form.html.twig', [
            'form'  => $form->createView(),
            'plan'  => null,
            'title' => 'Nouveau Plan',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Modifier un plan existant
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/plans/{id}/edit', name: 'app_abonnement_plan_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editPlan(int $id, Request $request): Response
    {
        $plan = $this->repository->find($id);
        if (!$plan || !$plan->isTemplate()) {
            throw $this->createNotFoundException('Plan introuvable.');
        }

        $form = $this->createForm(\App\Form\AbonnementPlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', '✅ Plan mis à jour.');
            return $this->redirectToRoute('app_abonnement_plans_admin');
        }

        return $this->render('abonnement/plan_form.html.twig', [
            'form'  => $form->createView(),
            'plan'  => $plan,
            'title' => 'Modifier le Plan',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Supprimer un plan
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/plans/{id}/delete', name: 'app_abonnement_plan_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePlan(int $id, Request $request): Response
    {
        $plan = $this->repository->find($id);
        if ($plan && $plan->isTemplate() && $this->isCsrfTokenValid('delete_plan_' . $id, $request->request->get('_token'))) {
            $this->em->remove($plan);
            $this->em->flush();
            $this->addFlash('success', '🗑 Plan supprimé.');
        }
        return $this->redirectToRoute('app_abonnement_plans_admin');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW — Détails d'une souscription
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/{id}/details', name: 'app_abonnement_show', methods: ['GET'])]
    public function showWeb(int $id): Response
    {
        $abonnement = $this->repository->find($id);
        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        return $this->render('abonnement/show.html.twig', [
            'abonnement' => $abonnement,
        ]);
    }

    // ===== BADGE PDF =====
    #[Route('/{id}/badge', name: 'app_abonnement_badge', methods: ['GET'])]
    public function downloadBadge(int $id): Response
    {
        $abonnement = $this->repository->find($id);

        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement non trouvé.');
        }

        // Ownership check
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($abonnement->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Accès refusé au badge.');
            }
        }

        $participantName = $user ? $user->getUserIdentifier() : 'Participant';
        $pdfContent = $this->badgeService->generateAbonnementBadgePdf($abonnement, $participantName);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="pass-lamma-' . $id . '.pdf"',
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Confirmer / Suspendre une souscription
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/{id}/confirmer', name: 'app_abonnement_confirmer', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function confirmer(int $id): Response
    {
        $abonnement = $this->repository->find($id);
        if ($abonnement) {
            $abonnement->setStatut(Abonnement::STATUT_ACTIF);
            $this->em->flush();
            $this->addFlash('success', '✅ Abonnement activé avec succès.');
        }
        return $this->redirectToRoute('app_abonnement_show', ['id' => $id]);
    }

    #[Route('/{id}/suspendre', name: 'app_abonnement_suspendre', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function suspendre(int $id): Response
    {
        $abonnement = $this->repository->find($id);
        if ($abonnement) {
            $abonnement->setStatut(Abonnement::STATUT_SUSPENDU);
            $this->em->flush();
            $this->addFlash('success', '⏸ Abonnement suspendu.');
        }
        return $this->redirectToRoute('app_abonnement_show', ['id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUPPRIMER une souscription
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/{id}/delete/web', name: 'app_abonnement_delete', methods: ['POST'])]
    public function deleteWeb(int $id, Request $request): Response
    {
        $abonnement = $this->repository->find($id);

        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user   = $this->getUser();
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($abonnement->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->em->remove($abonnement);
            $this->em->flush();
            $this->addFlash('success', 'Abonnement supprimé');
        }
        return $this->redirectToRoute('app_abonnement_index');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API JSON (conservés)
    // ─────────────────────────────────────────────────────────────────────────
    #[Route('/api/{id}', name: 'abonnement_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $abonnement = $this->repository->find($id);
        if (!$abonnement) return new JsonResponse(['error' => 'Abonnement non trouvé'], 404);
        return new JsonResponse($abonnement);
    }

    #[Route('/api/{id}', name: 'abonnement_delete_api', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $abonnement = $this->repository->find($id);
        if (!$abonnement) return new JsonResponse(['success' => false]);
        $this->em->remove($abonnement);
        $this->em->flush();
        return new JsonResponse(['success' => true]);
    }
}
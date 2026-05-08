<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/participations')]
#[IsGranted('ROLE_USER')]
class ParticipationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParticipationRepository $repository,
        private \App\Service\SmsService $smsService,
        private \App\Service\BadgeService $badgeService
    ) {}

    // ===== INDEX =====
    #[Route('/', name: 'app_participation_index', methods: ['GET'])]
    public function index(\App\Service\SmartAIService $aiService): Response
    {
        $qb = $this->repository->createQueryBuilder('p');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $qb->andWhere('p.userId = :user')
               ->setParameter('user', method_exists($user, 'getId') ? $user->getId() : 0);
        }

        $participations = $qb->getQuery()->getResult();
        
        // Fetch event titles to avoid showing IDs in the list
        $evenementRepo = $this->em->getRepository(\App\Entity\Evenement::class);
        $userRepo = $this->em->getRepository(\App\Entity\Users::class); // Assuming entity name is Users
        $eventMap = [];
        $userMap = [];
        $aiInsights = [];
        
        foreach ($participations as $p) {
            if ($p->getEvenementId() && !isset($eventMap[$p->getEvenementId()])) {
                $event = $evenementRepo->find($p->getEvenementId());
                if ($event) {
                    $eventMap[$p->getEvenementId()] = $event;
                    // AI Insight for this event
                    $aiInsights[$p->getEvenementId()] = [
                        'popularity' => $aiService->calculatePopularity($event),
                        'analysis' => $aiService->analyzeEvent($event->getTitre(), $event->getDescription())
                    ];
                }
            }
            if ($p->getUserId() && !isset($userMap[$p->getUserId()])) {
                $userObj = $userRepo->find($p->getUserId());
                if ($userObj) {
                    $userMap[$p->getUserId()] = $userObj->getName(); // Assuming getName() exists
                }
            }
        }

        return $this->render('participation/index.html.twig', [
            'items' => $participations,
            'eventMap' => $eventMap,
            'userMap' => $userMap,
            'aiInsights' => $aiInsights,
        ]);
    }

    // ===== NEW =====
    #[Route('/new', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function newWeb(Request $request): Response
    {
        $participation = new Participation();

        $eventId = $request->query->get('event_id');
        if ($eventId) {
            $evenement = $this->em->getRepository(\App\Entity\Evenement::class)->find($eventId);
            if ($evenement) {
                $participation->setEvenementId($evenement->getIdEvent());
            }
        }

        $form = $this->createForm(\App\Form\ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $evenement = $form->get('evenement')->getData();
            if ($evenement) {
                $participation->setEvenementId($evenement->getIdEvent());
            }

            $user = $this->getUser();
            $participation->setUserId(method_exists($user, 'getId') ? $user->getId() : 0);

            // valeurs par défaut
            $participation->setStatut(Participation::STATUT_EN_ATTENTE);

            // 🔥 Calcul automatique
            $participation->calculerTotalParticipants();

            // 🔥 Gestion abonnement
            $wantAbonnement = $request->request->get('want_abonnement');

            if ($wantAbonnement === '1') {
                $participation->setTypeAbonnementChoisi('OUI');
            } else {
                $participation->setTypeAbonnementChoisi('NON');
            }

            // 🔥 Calcul montant simple (optionnel)
            $montant = (
                $participation->getNbAdultes() * 25 +
                $participation->getNbEnfants() * 12.5 +
                $participation->getHebergementNuits() * 40
            );

            if ($participation->getMealOption() && $participation->getMealOption() !== 'SANS_REPAS') {
                $montant += ($participation->getNbAdultes() + $participation->getNbEnfants()) * 15;
            }

            $participation->setMontantCalcule((string)$montant);

            $this->em->persist($participation);
            $this->em->flush();

            // 🔥 Création du Ticket / Badge / Pass
            $generateType = $form->get('generateType')->getData();
            if ($generateType) {
                $ticket = new \App\Entity\Ticket();
                $ticket->setParticipationId($participation->getId());
                $ticket->setUserId($participation->getUserId());
                $ticket->setType($generateType);
                $ticket->setQrCode($generateType . '-' . uniqid());
                $this->em->persist($ticket);
                $this->em->flush();
            }

            // 🔥 SMS Notification
            try {
                $this->smsService->sendWelcomeSms("+21629051913");
            } catch (\Exception $e) {
                // SMS failure should not block the flow
            }


            $this->addFlash('success', '✅ Inscription réussie !');
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/new.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
        ]);
    }

    // ===== SHOW =====
    #[Route('/{id}', name: 'app_participation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $participation = $this->repository->find($id);

        if (!$participation) {
            throw $this->createNotFoundException('Participation introuvable.');
        }

        // Ownership check for non-admin users
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($participation->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette participation.');
            }
        }

        // Fetch event name for display
        $evenement = null;
        if ($participation->getEvenementId()) {
            $evenementRepo = $this->em->getRepository(\App\Entity\Evenement::class);
            $evenement = $evenementRepo->find($participation->getEvenementId());
        }

        return $this->render('participation/show.html.twig', [
            'item' => $participation,
            'event' => $evenement,
        ]);
    }

    // ===== BADGE PDF =====
    #[Route('/{id}/badge', name: 'app_participation_badge', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadBadge(int $id): Response
    {
        $participation = $this->repository->find($id);

        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée.');
        }

        // Ownership check
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($participation->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Accès refusé au badge.');
            }
        }

        // Get participant name from session user
        $user = $this->getUser();
        $participantName = $user ? $user->getUserIdentifier() : 'Participant';

        $pdfContent = $this->badgeService->generateBadgePdf($participation, $participantName);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="badge-participation-' . $id . '.pdf"',
        ]);
    }


    // ===== EDIT =====
    #[Route('/{id}/edit', name: 'app_participation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $participation = $this->repository->find($id);

        if (!$participation) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(\App\Form\ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $evenement = $form->get('evenement')->getData();
            if ($evenement) {
                $participation->setEvenementId($evenement->getId());
            }

            $participation->calculerTotalParticipants();

            $this->em->flush();

            $this->addFlash('success', '✏️ Modification réussie');

            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ===== CONFIRMER =====
    #[Route('/{id}/confirmer', name: 'app_participation_confirmer', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function confirmer(int $id): Response
    {
        $participation = $this->repository->find($id);

        if ($participation) {
            $participation->confirmer(); // Uses internal business logic method
            $this->em->flush();
            $this->addFlash('success', '✅ Participation confirmée avec succès.');
        }

        return $this->redirectToRoute('app_participation_show', ['id' => $id]);
    }

    // ===== ANNULER =====
    #[Route('/{id}/annuler', name: 'app_participation_annuler', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function annuler(int $id): Response
    {
        $participation = $this->repository->find($id);

        if ($participation) {
            $participation->annuler(); // Uses internal business logic method
            $this->em->flush();
            $this->addFlash('success', '❌ Participation annulée.');
        }

        return $this->redirectToRoute('app_participation_show', ['id' => $id]);
    }

    // ===== DELETE =====
    #[Route('/{id}/delete', name: 'app_participation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        $item = $this->repository->find($id);

        if (!$item) {
            throw $this->createNotFoundException();
        }

        // Ownership check
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            if ($item->getUserId() !== $userId) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette participation.');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $this->em->remove($item);
            $this->em->flush();
            $this->addFlash('success', 'Participation supprimée');
        }

        return $this->redirectToRoute('app_participation_index');
    }
}
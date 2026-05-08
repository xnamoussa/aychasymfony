<?php
namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Evenement;
use App\Form\EvenementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenement')]
final class EvenementController extends AbstractController
{
    #[Route('/switch-role/{role}', name: 'app_switch_role', methods: ['GET'])]
    public function switchRole(string $role, Request $request): Response
    {
        // For unified integration, we redirect based on the ACTUAL role
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        return $this->redirectToRoute('app_home');
    }

    #[Route(name: 'app_evenement_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EntityManagerInterface $entityManager, 
        \Knp\Component\Pager\PaginatorInterface $paginator,
        \App\Service\SmartAIService $aiService
    ): Response
    {
        $search = $request->query->get('search');
        $sort = $request->query->get('sortBy');

        $qb = $entityManager->getRepository(Evenement::class)->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($sort) {
            $sortOrder = $request->query->get('order', 'ASC');
            $validSorts = ['titre', 'date_debut', 'date_fin', 'nb_vues', 'lieu'];
            if (in_array($sort, $validSorts)) {
                $qb->orderBy('e.' . $sort, $sortOrder);
            }
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // --- SMART AI INTEGRATION ---
        $recommendations = [];
        $aiInsights = [];

        // Process AI for all events in current page
        foreach ($pagination->getItems() as $event) {
            $aiInsights[$event->getIdEvent()] = [
                'popularity' => $aiService->calculatePopularity($event),
                'analysis' => $aiService->analyzeEvent($event->getTitre(), $event->getDescription())
            ];
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $session = $request->getSession();
            $userPrefs = $session->get('ai_user_preferences', ['tech', 'music', 'sport']); // Default preferences
            
            // Get all events for recommendation (simplified for demo)
            $allEvents = $entityManager->getRepository(Evenement::class)->findAll();
            $recommendations = $aiService->recommendEvents($userPrefs, $allEvents);
            // Only keep top 3
            $recommendations = array_slice($recommendations, 0, 3);
        }
        // -----------------------------

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('admin_evenement/index.html.twig', [
                'evenements' => $pagination,
                'aiInsights' => $aiInsights,
            ]);
        }

        return $this->render('evenement/index.html.twig', [
            'evenements' => $pagination,
            'recommendations' => $recommendations,
            'aiInsights' => $aiInsights,
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Persist the event
                    $entityManager->persist($evenement);

                    $equipmentsString = $form->get('recommended_equipments')->getData();
                    if ($equipmentsString) {
                        $equipmentsList = explode(',', $equipmentsString);
                        foreach ($equipmentsList as $equipmentName) {
                            $equipmentName = trim($equipmentName);
                            if ($equipmentName !== '') {
                                $equipment = new Equipment();
                                $equipment->setLibelle($equipmentName);
                                $equipment->setEvent_id($evenement);
                                $entityManager->persist($equipment);
                            }
                        }
                    }

                    $entityManager->flush();
                    
                    $this->addFlash('success', 'L\'événement "' . $evenement->getTitre() . '" a été créé avec succès.');
                    $this->addFlash('ask_add_prog', $evenement->getId_event());
                    
                    return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
            }
        }

        return $this->render('admin_evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement, EntityManagerInterface $entityManager, Request $request, \App\Service\SmartAIService $aiService): Response
    {
        // REQUIREMENT: Every ROLE_USER click must increment views. 
        // We remove any session or uniqueness checks to ensure each visit adds +1.
        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN')) {
            $evenement->setNbVues($evenement->getNbVues() + 1);
            
            // Smart AI Learning
            $analysis = $aiService->analyzeEvent($evenement->getTitre(), $evenement->getDescription());
            $session = $request->getSession();
            $userPrefs = $session->get('ai_user_preferences', []);
            $newUserPrefs = $aiService->updateUserPreferences($userPrefs, $analysis['tags'], 'view');
            $session->set('ai_user_preferences', $newUserPrefs);

            $entityManager->flush();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('admin_evenement/show.html.twig', [
                'evenement' => $evenement,
            ]);
        }

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/api/views/{id}', name: 'app_evenement_get_views', methods: ['GET'])]
    public function getViews(Evenement $evenement): Response
    {
        return $this->json(['views' => $evenement->getNbVues()]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        if ($this->isCsrfTokenValid('delete'.$evenement->getId_event(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}


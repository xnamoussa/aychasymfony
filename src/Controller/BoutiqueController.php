<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Delivery;
use App\Entity\Equipements;
use App\Entity\Users;
use App\Form\DeliveryType;
use App\Form\EquipementsType;
use App\Repository\EquipementsRepository;
use App\Repository\TransactionRepository;
use App\Repository\UsersRepository;
use App\Service\EquipementsVueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DeliveryCostAiEstimator;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BoutiqueController extends AbstractController
{
    public function __construct(
        private readonly EquipementsRepository $equipementRepository,
        private readonly EntityManagerInterface $em,
        private readonly EquipementsVueService $equipementVueService,
        private readonly MailerInterface $mailer,
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/boutique', name: 'app_boutique', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();

        $all = $this->equipementRepository->findAllOrderedByDateDesc();
        $qRaw = trim($request->query->getString('q'));
        $cat = $request->query->getString('categorie');
        $catFilter = $cat === '' || strcasecmp($cat, 'Toutes catégories') === 0 ? null : $cat;

        $categories = $this->collectCategories($all);
        array_unshift($categories, 'Toutes catégories');

        $filtered = $this->filterEquipements($all, $qRaw, $catFilter);
        
        // Hide delivered items from non-owners to keep the catalog clean
        $filtered = array_filter($filtered, function (Equipements $e) use ($currentUser) {
            if ($e->getDelivery() && $e->getDelivery()->getStatut() === 'livree') {
                return $currentUser && $e->getOwner() && $e->getOwner()->getId() === $currentUser->getId();
            }
            return true;
        });
        $filtered = $this->orderEquipementsForDisplay($filtered, $catFilter);

        $viewsCount = $this->buildViewsCountForList($filtered);

        return $this->render('boutique/index.html.twig', [
            'equipements' => $filtered,
            'categories' => $categories,
            'current_categorie' => $catFilter ?? 'Toutes catégories',
            'q' => $qRaw,
            'views_count' => $viewsCount,
            'current_user' => $currentUser,
            'livraisons' => $currentUser ? $currentUser->getLivraisons() : [],
            'open_meteo_apiUrl' => 'https://api.open-meteo.com/v1/forecast'
        ]);
    }

    #[Route('/store', name: 'app_store')]
    public function storeAlias(): Response
    {
        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/boutique/recherche', name: 'app_boutique_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $all = $this->equipementRepository->findAllOrderedByDateDesc();
        $qRaw = trim($request->query->getString('q'));
        $cat = $request->query->getString('categorie');
        $catFilter = $cat === '' || strcasecmp($cat, 'Toutes catégories') === 0 ? null : $cat;

        $filtered = $this->filterEquipements($all, $qRaw, $catFilter);
        $filtered = $this->orderEquipementsForDisplay($filtered, $catFilter);
        $viewsCount = $this->buildViewsCountForList($filtered);

        $html = $this->renderView('boutique/_cards.html.twig', [
            'equipements' => $filtered,
            'views_count' => $viewsCount,
        ]);

        return new JsonResponse([
            'html' => $html,
            'count' => \count($filtered),
        ]);
    }

    #[Route('/mes-livraisons', name: 'app_my_deliveries')]
    public function myDeliveries(): Response
    {
        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('boutique/my_deliveries.html.twig', [
            'livraisons' => $currentUser->getLivraisons(),
        ]);
    }

    #[Route('/boutique/{id}', name: 'app_boutique_show', requirements: ['id' => '\d+'])]
    public function show(string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException();
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        
        // Strict identification comparison
        $ownerId = $equipement->getOwner() ? $equipement->getOwner()->getId() : null;
        $currentUserId = $currentUser ? $currentUser->getId() : null;
        $isOwner = ($ownerId !== null && $currentUserId !== null && $ownerId === $currentUserId);
        
        if ($currentUser) {
            $this->equipementVueService->registerView($equipement, (string)$currentUser->getId());
        }

        $delivery = $equipement->getDelivery();
        $isBuyer = $currentUser && $delivery && $delivery->getAcheteur() && ($delivery->getAcheteur()->getId() === $currentUserId);
        
        // "Make delivery" (Ordering) available only if logged in, not owner, livrable, and no delivery yet
        $canOrder = $currentUser && !$isOwner && $equipement->isLivrable() && !$delivery;
        $canViewDelivery = $currentUser && $delivery && ($isBuyer || $isOwner || $this->isGranted('ROLE_ADMIN'));

        $transaction = $this->transactionRepository->findOneBy(['equipement' => $equipement]);

        return $this->render('boutique/show.html.twig', [
            'equipement' => $equipement,
            'views_count' => $this->equipementVueService->getViewsCount($equipement),
            'current_user' => $currentUser,
            'can_manage' => $isOwner || $this->isGranted('ROLE_ADMIN'),
            'is_buyer' => $isBuyer,
            'can_order' => $canOrder,
            'can_view_delivery' => $canViewDelivery,
            'has_transaction' => $transaction !== null,
        ]);
    }

    #[Route('/boutique/{id}/commander', name: 'app_boutique_order', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function order(Request $request, string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException();
        }

        if (!$equipement->isLivrable()) {
            $this->addFlash('error', 'Cet équipement n\'est pas livrable.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        if ($equipement->getDelivery()) {
            $this->addFlash('error', 'Une commande est déjà enregistrée pour cet équipement.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            $this->addFlash('error', 'Veuillez vous connecter pour commander.');
            return $this->redirectToRoute('app_login');
        }

        $isOwner = false;
        if ($equipement->getOwner()) {
            $isOwner = ($equipement->getOwner()->getId() === $currentUser->getId());
        }
        
        if ($isOwner) {
            $this->addFlash('error', 'Vous ne pouvez pas commander votre propre équipement.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('order_equipement_' . $id, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $delivery = new Delivery();
        $delivery->setEquipement($equipement);
        $delivery->setAcheteur($currentUser);
        $delivery->setStatut('en_attente');

        $this->em->persist($delivery);
        $this->em->flush();

        $this->addFlash('success', 'Commande enregistrée. Complétez vos coordonnées de livraison.');

        return $this->redirectToRoute('app_boutique_delivery', ['id' => $id]);
    }

    #[Route('/boutique/{id}/livraison', name: 'app_boutique_delivery', requirements: ['id' => '\d+'])]
    public function delivery(Request $request, string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException();
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $delivery = $equipement->getDelivery();
        if (!$delivery) {
            $this->addFlash('error', 'Aucune livraison associée pour cet équipement.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        $isOwner = ($equipement->getOwner() && $equipement->getOwner()->getId() === $currentUser->getId()) || $this->isGranted('ROLE_ADMIN');
        $isBuyer = ($delivery->getAcheteur() && $delivery->getAcheteur()->getId() === $currentUser->getId());
        
        if (!$isOwner && !$isBuyer) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette livraison.');
        }

        $form = $this->createForm(DeliveryType::class, $delivery, [
            'status_editable' => $isOwner,
            'address_editable' => $isBuyer,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isBuyer && !$delivery->getEstimation() && $delivery->getLatitude() && $delivery->getLongitude()) {
                // Mock estimation
                $delivery->setEstimation((new \DateTime())->modify('+3 days'));
            }

            $this->em->flush();
            if ($isOwner) {
                $this->addFlash('success', 'État de livraison mis à jour.');
                return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
            }

            $this->addFlash('success', 'Coordonnées enregistrées. Veuillez procéder au paiement.');
            return $this->redirectToRoute('app_boutique_payment', ['id' => $id]);
        }

        return $this->render('boutique/delivery.html.twig', [
            'equipement' => $equipement,
            'delivery' => $delivery,
            'form' => $form->createView(),
            'isOwner' => $isOwner,
            'isBuyer' => $isBuyer,
        ]);
    }

    #[Route('/delivery/estimate-cost', name: 'app_delivery_estimate', methods: ['POST'])]
    public function estimateCost(Request $request, DeliveryCostAiEstimator $aiEstimator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $distanceKm = isset($data['distance_km']) ? (float)$data['distance_km'] : 0.0;

        if ($distanceKm <= 0) {
            return new JsonResponse(['error' => 'Invalid distance'], 400);
        }

        $estimation = $aiEstimator->estimate($distanceKm);
        return new JsonResponse($estimation);
    }

    #[Route('/boutique/{id}/paiement', name: 'app_boutique_payment', requirements: ['id' => '\d+'])]
    public function payment(string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement || !$equipement->getDelivery()) {
            throw $this->createNotFoundException();
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        $delivery = $equipement->getDelivery();
        $isBuyer = $currentUser && $delivery->getAcheteur() && ($delivery->getAcheteur()->getId() === $currentUser->getId());
        
        if (!$isBuyer) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas payer cette commande.');
        }

        return $this->render('boutique/payment.html.twig', [
            'equipement' => $equipement,
            'delivery' => $delivery,
        ]);
    }

    #[Route('/boutique/{id}/paiement/valider', name: 'app_boutique_payment_process', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function processPayment(Request $request, string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement || !$equipement->getDelivery()) {
            throw $this->createNotFoundException();
        }
        
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('process_payment_'.$id, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $delivery = $equipement->getDelivery();
        $delivery->setStatut('preparation');
        
        $transaction = new Transaction();
        $transaction->setEquipement($equipement);
        
        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        if($currentUser) {
            $transaction->setBuyer($currentUser);
        } else {
            $transaction->setBuyer($delivery->getAcheteur());
        }
        
        $transaction->setSeller($equipement->getOwner());
        $frais = $delivery->getFraisLivraison() ?? 0.0;
        $totalPrice = (float) $equipement->getPrix() + $frais;
        $transaction->setPrice((string)$totalPrice);
        $transaction->setStripeToken('mock_token_' . bin2hex(random_bytes(8)));
        
        $this->em->persist($transaction);
        $equipement->setStatut('VENDU');

        $this->em->flush();

        $this->addFlash('success', 'Paiement effectué avec succès. Votre commande est en préparation.');
        return $this->redirectToRoute('app_my_deliveries');
    }

    #[Route('/boutique/nouveau', name: 'app_boutique_new')]
    public function new(Request $request): Response
    {
        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un équipement.');
            return $this->redirectToRoute('app_login');
        }

        $equipement = new Equipements();
        $equipement->setOwner($currentUser);
        $form = $this->createForm(EquipementsType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $equipement->setDateAjout(new \DateTime());
            $equipement->setStatut($equipement->getStatut() ?: 'DISPONIBLE');
            $delivery = $equipement->getDelivery();
            if ($delivery) {
                $delivery->setEquipement($equipement);
            }
            $this->em->persist($equipement);
            $this->em->flush();

            $this->maybeSendNotificationEmail($equipement, $equipement->getMail());
            $this->addFlash('success', 'Équipement ajouté !');

            return $this->redirectToRoute('app_boutique');
        }

        return $this->render('boutique/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvel équipement',
            'current_user' => $currentUser,
        ]);
    }

    #[Route('/boutique/{id}/modifier', name: 'app_boutique_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException();
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        $ownerId = $equipement->getOwner() ? $equipement->getOwner()->getId() : null;
        $isOwner = ($currentUser && $ownerId !== null && $ownerId === $currentUser->getId()) || $this->isGranted('ROLE_ADMIN');
        
        if (!$isOwner) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres équipements.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        $form = $this->createForm(EquipementsType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Équipement modifié !');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        return $this->render('boutique/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier équipement',
            'equipement' => $equipement,
            'current_user' => $currentUser,
        ]);
    }

    #[Route('/boutique/{id}/supprimer', name: 'app_boutique_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, string $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException();
        }

        /** @var Users|null $currentUser */
        $currentUser = $this->getUser();
        $ownerId = $equipement->getOwner() ? $equipement->getOwner()->getId() : null;
        $isOwner = ($currentUser && $ownerId !== null && $ownerId === $currentUser->getId()) || $this->isGranted('ROLE_ADMIN');
        
        if (!$isOwner) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres équipements.');
            return $this->redirectToRoute('app_boutique_show', ['id' => $id]);
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('delete_equipement_'.$id, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->em->remove($equipement);
        $this->em->flush();
        $this->addFlash('success', 'Équipement supprimé.');

        return $this->redirectToRoute('app_boutique');
    }

    /**
     * @param Equipements[] $all
     * @return string[]
     */
    private function collectCategories(array $all): array
    {
        $set = [];
        foreach ($all as $e) {
            $c = $e->getCategorie();
            if ($c !== null && trim($c) !== '') {
                $set[mb_strtolower(trim($c))] = trim($c);
            }
        }
        $list = array_values($set);
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * @param Equipements[] $all
     * @return Equipements[]
     */
    private function filterEquipements(array $all, string $qRaw, ?string $catFilter): array
    {
        return array_values(array_filter($all, function (Equipements $e) use ($qRaw, $catFilter) {
            $matchSearch = true;
            if ($qRaw !== '') {
                $haystack = mb_strtolower($e->getNom() . ' ' . $e->getDescription() . ' ' . $e->getCategorie(), 'UTF-8');
                $matchSearch = str_contains($haystack, mb_strtolower($qRaw, 'UTF-8'));
            }
            $matchCat = $catFilter === null || strcasecmp(trim($e->getCategorie() ?? ''), trim($catFilter)) === 0;
            return $matchSearch && $matchCat;
        }));
    }

    /**
     * @param Equipements[] $list
     * @return Equipements[]
     */
    private function orderEquipementsForDisplay(array $list, ?string $catFilter): array
    {
        usort($list, function (Equipements $a, Equipements $b) {
            return $b->getDateAjout() <=> $a->getDateAjout();
        });
        return $list;
    }

    /**
     * @param Equipements[] $equipements
     * @return array<int, int>
     */
    private function buildViewsCountForList(array $equipements): array
    {
        $viewsCount = [];
        foreach ($equipements as $e) {
            $viewsCount[$e->getId()] = $this->equipementVueService->getViewsCount($e);
        }
        return $viewsCount;
    }

    private function maybeSendNotificationEmail(Equipements $e, mixed $to): void
    {
        if (!is_string($to) || trim($to) === '') return;
        try {
            $email = (new Email())
                ->from('noreply@lamma.local')
                ->to(trim($to))
                ->subject('Équipement ajouté – LAMMA')
                ->text(sprintf("L'équipement %s a été ajouté.", $e->getNom()));
            $this->mailer->send($email);
        } catch (\Throwable) {}
    }
}

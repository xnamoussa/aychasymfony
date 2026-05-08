<?php

namespace App\Controller;

use App\Service\CartService;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ticket;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index')]
    public function index(CartService $cartService): Response
    {
        $promo = $cartService->getAppliedPromo();
        
        return $this->render('cart/index.html.twig', [
            'items' => $cartService->getFullCart(),
            'total' => $cartService->getTotal(),
            'discount' => $cartService->getDiscountAmount(),
            'discountedTotal' => $cartService->getDiscountedTotal(),
            'appliedPromo' => $promo
        ]);
    }

    #[Route('/apply-promo', name: 'app_cart_apply_promo', methods: ['POST'])]
    public function applyPromo(Request $request, CartService $cartService, \App\Repository\CodePromoRepository $promoRepo): Response
    {
        $code = $request->request->get('code');
        $promo = $promoRepo->findOneBy(['code' => $code]);

        if ($promo && $promo->canBeUsed()) {
            $cartService->setPromoCode($promo);
            $this->addFlash('success', 'Code promo "' . $code . '" appliqué ! (-' . $promo->getDiscountPercentage() . '%)');
        } else {
            $this->addFlash('error', 'Code promo invalide ou expiré.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove-promo', name: 'app_cart_remove_promo')]
    public function removePromo(CartService $cartService): Response
    {
        $cartService->setPromoCode(null);
        $this->addFlash('info', 'Code promo retiré.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(int $id, CartService $cartService, Request $request): Response
    {
        $cartService->add($id);
        $this->addFlash('success', 'Plat ajouté au panier avec succès !');

        // Redirect back to the page they came from, or cart
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cart_index');
    }

    #[Route('/add-custom/{id}', name: 'app_cart_add_custom', methods: ['POST'])]
    public function addCustom(int $id, Request $request, CartService $cartService): Response
    {
        $supplements = $request->request->all('supplements');
        // Clean up supplements to remove zeros
        $supplements = array_filter($supplements, fn($q) => $q > 0);
        
        $cartService->addCustom($id, $supplements);
        $this->addFlash('success', 'Plat personnalisé ajouté au panier !');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/add-api-meal', name: 'app_cart_add_api_meal', methods: ['POST'])]
    public function addApiMeal(Request $request, CartService $cartService, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $nom = $request->request->get('nom', 'Plat Surprise');
        $desc = $request->request->get('description', 'Recette TheMealDB');
        $img = $request->request->get('imageUrl', '');

        $repas = new \App\Entity\RepasDetaille();
        $repas->setNom($nom);
        $repas->setDescription(substr($desc, 0, 500)); // Truncate safely
        $repas->setPrix('15.00'); // Fixed surprise price
        $repas->setImageUrl($img);
        $repas->setActif(false); // Hide from public main catalog
        $repas->setVegetarien(false);
        $repas->setVegan(false);
        $repas->setSansGluten(false);
        $repas->setHalal(false);

        $em->persist($repas);
        $em->flush();

        // Add to cart directly
        $cartService->add($repas->getId());

        return $this->json(['success' => true, 'message' => 'Plat surprise ajouté au panier !']);
    }

    #[Route('/remove/{hash}', name: 'app_cart_remove')]
    public function remove(string $hash, CartService $cartService): Response
    {
        $cartService->remove($hash);
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(CartService $cartService, UrlGeneratorInterface $urlGenerator): Response
    {
        $cart = $cartService->getFullCart();

        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $promo = $cartService->getAppliedPromo();
        $discountFactor = $promo ? (1 - ($promo->getDiscountPercentage() / 100)) : 1.0;

        $lineItems = [];
        foreach ($cart as $item) {
            $repas = $item['repas'];
            $name = $repas->getNom();
            
            if (!empty($item['supplements'])) {
                $suppNames = [];
                foreach ($item['supplements'] as $s) {
                    $suppNames[] = $s['qty'] . 'x ' . $s['ingredient']->getNom();
                }
                $name .= ' (' . implode(', ', $suppNames) . ')';
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Plat : ' . $name,
                    ],
                    'unit_amount' => (int) round($item['prixUnitaireTotal'] * $discountFactor * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $checkout_session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('app_cart_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $urlGenerator->generate('app_cart_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($checkout_session->url, 303);
    }

    #[Route('/payment/success', name: 'app_cart_payment_success')]
    public function success(Request $request, CartService $cartService, EntityManagerInterface $em): Response
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            return $this->redirectToRoute('app_cart_index');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        try {
            $session = Session::retrieve($sessionId);
            if ($session->payment_status === 'paid') {
                
                $cart = $cartService->getFullCart();
                $user = $this->getUser();
                $userId = method_exists($user, 'getId') ? $user->getId() : 0;

                foreach ($cart as $item) {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $ticket = new Ticket();
                        $ticket->setUserId($userId);
                        $ticket->setType('REPAS');
                        $ticket->setQrCode('REPAS-' . $item['repas']->getId() . '-' . uniqid());
                        $em->persist($ticket);
                    }
                }
                $em->flush();

                // --- UPDATE PROMO USAGE ---
                if ($promoId = $request->getSession()->get('applied_promo_id')) {
                    $promo = $em->getRepository(\App\Entity\CodePromo::class)->find($promoId);
                    if ($promo) {
                        $promo->use(); 
                        $em->flush();
                    }
                }

                $cartService->clear();

                $this->addFlash('success', '🎉 MISSION ACCOMPLIE ! Votre commande a été payée. Retrouvez vos tickets repas dans votre profil.');
                return $this->redirectToRoute('app_ticket_index');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du paiement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_index');
    }
}

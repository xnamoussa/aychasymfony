<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\RepasDetailleRepository;

class CartService
{
    private RequestStack $requestStack;
    private RepasDetailleRepository $repasRepository;
    private \App\Repository\IngredientRepository $ingredientRepository;
    private \App\Repository\CodePromoRepository $promoRepository;

    public function __construct(
        RequestStack $requestStack, 
        RepasDetailleRepository $repasRepository,
        \App\Repository\IngredientRepository $ingredientRepository,
        \App\Repository\CodePromoRepository $promoRepository
    ) {
        $this->requestStack = $requestStack;
        $this->repasRepository = $repasRepository;
        $this->ingredientRepository = $ingredientRepository;
        $this->promoRepository = $promoRepository;
    }

    public function add(int $id): void
    {
        $this->addCustom($id, []);
    }

    /**
     * @param array<int, int> $supplements
     */
    public function addCustom(int $repasId, array $supplements = []): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        // Sort supplements to ensure consistent hashing regardless of array order
        ksort($supplements);
        $hashData = $repasId . '_' . json_encode($supplements);
        $hash = md5($hashData);

        if (!empty($cart[$hash])) {
            $cart[$hash]['qty']++;
        } else {
            $cart[$hash] = [
                'repasId' => $repasId,
                'qty' => 1,
                'supplements' => $supplements
            ];
        }

        $session->set('cart', $cart);
    }

    public function remove(string $hash): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        // Compatibilité avec l'ancienne méthode où $hash pouvait être un simple ID (int casts to string)
        if (isset($cart[$hash])) {
            unset($cart[$hash]);
        } else {
            // Check if user passed an ID instead of a hash from old views
            $idHash = md5($hash . '_' . json_encode([]));
            if (isset($cart[$idHash])) {
                unset($cart[$idHash]);
            }
        }

        $session->set('cart', $cart);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFullCart(): array
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $cartData = [];

        foreach ($cart as $hash => $data) {
            // Migration douceur des vieux paniers non hashés
            if (!is_array($data)) {
                $repasId = $hash;
                $qty = $data;
                $supplements = [];
            } else {
                $repasId = $data['repasId'];
                $qty = $data['qty'];
                $supplements = $data['supplements'];
            }

            $item = $this->repasRepository->find($repasId);

            if (!$item) {
                unset($cart[$hash]);
                $session->set('cart', $cart);
                continue;
            }

            // Calcul du prix et récupération des objets Ingredients
            $totalUnitaire = (float) $item->getPrix();
            $supplementsObj = [];

            foreach ($supplements as $ingId => $ingQty) {
                if ($ingQty > 0) {
                    $ingredient = $this->ingredientRepository->find($ingId);
                    if ($ingredient) {
                        $totalUnitaire += ((float) $ingredient->getPrixSupplement()) * $ingQty;
                        $supplementsObj[] = [
                            'ingredient' => $ingredient,
                            'qty' => $ingQty
                        ];
                    }
                }
            }

            $cartData[] = [
                'hash' => $hash,
                'repas' => $item,
                'quantity' => $qty,
                'supplements' => $supplementsObj,
                'prixUnitaireTotal' => $totalUnitaire
            ];
        }

        return $cartData;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getFullCart() as $item) {
            $total += $item['prixUnitaireTotal'] * $item['quantity'];
        }

        return $total;
    }

    public function countItems(): int
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $count = 0;
        foreach ($cart as $data) {
            $count += is_array($data) ? $data['qty'] : $data;
        }
        return $count;
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove('cart');
        $session->remove('applied_promo_id');
    }

    // --- PROMO CODE LOGIC ---

    public function setPromoCode(?\App\Entity\CodePromo $promo): void
    {
        $session = $this->requestStack->getSession();
        if ($promo) {
            $session->set('applied_promo_id', $promo->getId());
        } else {
            $session->remove('applied_promo_id');
        }
    }

    public function getAppliedPromo(): ?\App\Entity\CodePromo
    {
        $session = $this->requestStack->getSession();
        $promoId = $session->get('applied_promo_id');
        
        if (!$promoId) return null;
        
        $promo = $this->promoRepository->find($promoId);
        
        if (!$promo || !$promo->canBeUsed()) {
            $session->remove('applied_promo_id');
            return null;
        }
        
        return $promo;
    }

    public function getDiscountAmount(): float
    {
        $promo = $this->getAppliedPromo();
        if (!$promo) return 0.0;
        
        $total = $this->getTotal();
        return $total * ($promo->getDiscountPercentage() / 100);
    }

    public function getDiscountedTotal(): float
    {
        return $this->getTotal() - $this->getDiscountAmount();
    }
}

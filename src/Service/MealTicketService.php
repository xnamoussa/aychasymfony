<?php
namespace App\Service;

use App\Entity\MealTicket;
use App\Repository\MealTicketRepository;
use App\Repository\ParticipationRepository;
use App\Repository\MenuRepository;
use App\Repository\RepasDetailleRepository;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class MealTicketService
{
    private EntityManagerInterface $em;
    private MealTicketRepository $mealTicketRepository;
    private ParticipationRepository $participationRepository;
    private MenuRepository $menuRepository;
    private RepasDetailleRepository $repasDetailleRepository;
    private IngredientRepository $ingredientRepository;

    public function __construct(
        EntityManagerInterface $em,
        MealTicketRepository $mealTicketRepository,
        ParticipationRepository $participationRepository,
        MenuRepository $menuRepository,
        RepasDetailleRepository $repasDetailleRepository,
        IngredientRepository $ingredientRepository
    ) {
        $this->em = $em;
        $this->mealTicketRepository = $mealTicketRepository;
        $this->participationRepository = $participationRepository;
        $this->menuRepository = $menuRepository;
        $this->repasDetailleRepository = $repasDetailleRepository;
        $this->ingredientRepository = $ingredientRepository;
    }

    /**
     * Génération de ticket QR unique, contrôle 1 repas / créneau / utilisateur
     */
    public function generateTicket(int $participationId, int $userId, \DateTimeInterface $timeSlot): MealTicket
    {
        // Contrôle : Uniquement 1 ticket pour ce créneau par utilisateur
        $existingTicket = $this->mealTicketRepository->findOneBy([
            'userId' => $userId,
            'timeSlot' => $timeSlot
        ]);

        if ($existingTicket) {
            throw new Exception("L'utilisateur a déjà un ticket de repas pour ce créneau.");
        }

        $qrCode = 'MEAL-' . strtoupper(uniqid()) . '-' . $userId;
        
        $ticket = new MealTicket();
        $ticket->initialiser($participationId, $userId, $qrCode, $timeSlot);
        
        $this->em->persist($ticket);
        $this->em->flush();
        
        return $ticket;
    }

    /**
     * Consommation via scan QR, déduction automatique du stock d'ingrédients, alertes seuils bas
     * @return array<string, mixed>
     */
    public function consumeTicket(string $qrCode): array
    {
        $ticket = $this->mealTicketRepository->findOneBy(['qrCode' => $qrCode]);
        
        if (!$ticket) {
            throw new Exception("Ticket introuvable ou QR Code invalide.");
        }
        
        if ($ticket->isUsed()) {
            throw new Exception("Ce ticket a déjà été consommé le " . $ticket->getUsedAt()->format('d/m/Y H:i:s'));
        }
        
        $ticket->utiliser();
        $this->em->persist($ticket);
        
        $alerts = [];
        
        // --- 1. Rechercher la participation liée ---
        $participationId = $ticket->getParticipationId();
        $participation = $this->participationRepository->find($participationId);
        
        if ($participation && $participation->getMenuId()) {
            // --- 2. Rechercher le Menu assigné ---
            $menu = $this->menuRepository->find($participation->getMenuId());
            
            if ($menu && !empty($menu->getDishesIds())) {
                foreach ($menu->getDishesIds() as $dishId) {
                    // --- 3. Pour chaque plat (RepasDetaille) dans la composition du menu ---
                    $dish = $this->repasDetailleRepository->find($dishId);
                    
                    if ($dish && !empty($dish->getIngredients())) {
                        foreach ($dish->getIngredients() as $ingredientId) {
                            // --- 4. Déduction du stock d'ingrédients ---
                            $ingredient = $this->ingredientRepository->find((int) $ingredientId);
                            
                            if ($ingredient) {
                                $newStock = $ingredient->getStockQuantite() - 1;
                                $ingredient->setStockQuantite($newStock);
                                $this->em->persist($ingredient);
                                
                                // --- 5. Notification de Seuil Bas ---
                                if ($ingredient->estSousSeuil()) {
                                    $alerts[] = sprintf(
                                        "Alerte de Stock : '%s' vient de passer sous le seuil d'alerte (Stock restant: %d)",
                                        $ingredient->getNom(),
                                        $newStock
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // --- Sauvegarder la consommation et la mise à jour des stocks ---
        $this->em->flush();
        
        return [
            'success' => true,
            'message' => 'Ticket validé et déduction de stock effectuée avec succès.',
            'alerts' => $alerts
        ];
    }
}

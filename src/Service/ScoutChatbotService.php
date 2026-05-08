<?php

namespace App\Service;

use App\Repository\IngredientRepository;
use App\Repository\ParticipationRepository;
use App\Repository\RepasDetailleRepository;
use App\Repository\RestaurantRepository;
use OpenAI;
use Exception;

class ScoutChatbotService
{
    private string $apiKey;
    private RepasDetailleRepository $repasRepository;
    private ParticipationRepository $participationRepository;
    private RestaurantRepository $restaurantRepository;

    public function __construct(
        RepasDetailleRepository $repasRepository,
        ParticipationRepository $participationRepository,
        RestaurantRepository $restaurantRepository
    ) {
        $this->apiKey = (string)($_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY'));
        $this->repasRepository = $repasRepository;
        $this->participationRepository = $participationRepository;
        $this->restaurantRepository = $restaurantRepository;
    }

    public function getAiResponse(string $userMessage): string
    {
        if (empty($this->apiKey)) {
            return "Désolé, l'assistant Scout est actuellement hors ligne (Clé API manquante).";
        }

        // 1. GATHER RICH CONTEXT
        $data = $this->collectFullMarketData();

        // 2. BUILD THE ULTIMATE KNOWLEDGE BASE
        $systemPrompt = "Tu es 'Scout', l'assistant IA de la plateforme LAMMA, expert en événementiel et restauration.
Ton objectif est de répondre à TOUTE question sur les services de LAMMA.

BASE DE DONNÉES TEMPS RÉEL :
- RESTAURANTS : " . json_encode(array_slice($data['restaurants'], 0, 15)) . "
- MENU COMPLET : " . json_encode(array_slice($data['meals'], 0, 25)) . "
- STATS PLATEFORME : " . count($data['participations']) . " participants, " . number_format($data['revenue'], 2) . "€ de revenus.

INSTRUCTIONS :
1. Sois chaleureux et expert.
2. Utilise les données pour comparer les prix, gérer les allergies (consulte 'allergenes' et 'flags'), et donner des infos nutritionnelles.
3. Si l'utilisateur est trop inquiet pour ses allergies, conseille-lui l'outil de 'Composition Personnalisée'.
4. Réponds en Français exclusivement.";

        try {
            $client = OpenAI::client($this->apiKey);

            // Switching to gpt-3.5-turbo for maximum compatibility with all API keys
            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
            ]);

            return $result->choices[0]->message->content ?? $this->generateFallbackResponse($userMessage, $data);

        } catch (\Throwable $e) {
            // Log for debug (if possible) or just smarter fallback
            return $this->generateFallbackResponse($userMessage, $data, strpos($e->getMessage(), '429') !== false);
        }
    }

    /** @return array<string, mixed> */
    private function collectFullMarketData(): array
    {
        $restaurants = array_slice($this->restaurantRepository->findAll(), 0, 30);
        $repas = array_slice($this->repasRepository->findAll(), 0, 50);
        $participations = array_slice($this->participationRepository->findAll(), 0, 100);

        $totalRevenue = 0;
        foreach ($participations as $p) {
            if ($p->getStatut() === 'CONFIRME') {
                $totalRevenue += (float)($p->getMontantCalcule() ?? 0);
            }
        }

        $restContext = [];
        foreach ($restaurants as $r) {
            if (!$r->isActif()) continue;
            $restContext[] = [
                'nom' => $r->getNom(),
                'adresse' => $r->getAdresse(),
                'note' => $r->getRating(),
                'ouvert' => $r->isOpen()
            ];
        }

        $mealContext = [];
        foreach ($repas as $m) {
            if (!$m->isActif()) continue;
            $mealContext[] = [
                'nom' => $m->getNom(),
                'prix' => $m->getPrix(),
                'description' => $m->getDescription(),
                'restaurant' => $this->getRestaurantName($m->getRestaurantId(), $restaurants),
                'calories' => $m->getCalories(),
                'proteines' => $m->getProteines(),
                'allergenes' => $m->getAllergenes(),
                'ingredients' => $m->getIngredients(),
                'flags' => [
                    'vege' => $m->isVegetarien(),
                    'vegan' => $m->isVegan(),
                    'sansGluten' => $m->isSansGluten(),
                    'halal' => $m->isHalal()
                ]
            ];
        }

        return [
            'restaurants' => $restContext,
            'meals' => $mealContext,
            'participations' => $participations,
            'revenue' => $totalRevenue
        ];
    }

    /** @param \App\Entity\Restaurant[] $restaurants */
    private function getRestaurantName(?int $id, array $restaurants): string
    {
        if (!$id) return "Inconnu";
        foreach ($restaurants as $r) {
            if ($r->getId() === $id) return $r->getNom();
        }
        return "Inconnu";
    }

    /**
     * INTELLIGENT FALLBACK: Actually searches local data when AI is down
     */
    /** @param array<string, mixed> $data */
    private function generateFallbackResponse(string $message, array $data, bool $isRateLimited = false): string
    {
        $m = strtolower($message);
        $resp = "";

        // --- NEW: Deep Allergy Intelligence (Associations) ---
        $allergyMap = [
            'lactose' => ['lait', 'fromage', 'beurre', 'crème', 'cheese', 'yaourt'],
            'gluten' => ['farine', 'pain', 'pâte', 'semoule', 'blé', 'pizza'],
            'arachide' => ['cacahuète', 'beurre de cacahuète', 'noix', 'amande'],
            'oeuf' => ['œuf', 'mayonnaise', 'omelette'],
        ];

        // 1. ALLERGY / RESTRICTION SEARCH
        if (strpos($m, 'allergi') !== false || strpos($m, 'gluten') !== false || strpos($m, 'halal') !== false || strpos($m, 'vege') !== false || strpos($m, 'lactose') !== false) {
            
            // Detect which allergy the user has
            $detectedAllergens = [];
            foreach ($allergyMap as $allergen => $synonyms) {
                if (strpos($m, $allergen) !== false) {
                    $detectedAllergens = array_merge($detectedAllergens, [$allergen], $synonyms);
                }
            }

            $filtered = [];
            $unsafeReason = "";

            foreach ($data['meals'] as $meal) {
                $safe = true;
                $reason = "";

                // Check direct flags
                if (strpos($m, 'gluten') !== false && !$meal['flags']['sansGluten']) { $safe = false; $reason = "contient du gluten"; }
                if (strpos($m, 'halal') !== false && !$meal['flags']['halal']) { $safe = false; $reason = "n'est pas certifié Halal"; }
                if (strpos($m, 'vege') !== false && !$meal['flags']['vege']) { $safe = false; $reason = "contient de la viande"; }
                
                // Check allergens & ingredients against keywords/synonyms
                $mealText = strtolower($meal['nom'] . ' ' . $meal['description'] . ' ' . implode(' ', $meal['ingredients']) . ' ' . implode(' ', $meal['allergenes']));
                
                foreach ($detectedAllergens as $keyword) {
                    if (strpos($mealText, $keyword) !== false) {
                        $safe = false;
                        $reason = "peut contenir : " . $keyword;
                        break;
                    }
                }

                if ($safe && count($filtered) < 5) {
                    $filtered[] = $meal;
                }
            }

            if (!empty($filtered)) {
                $resp = "🛡️ **Scout - Conseil Allergie** : Pour votre sécurité, j'ai filtré les plats. \n";
                if (!empty($detectedAllergens)) {
                    $resp .= "⚠️ *Attention : J'ai exclu tout ce qui semble contenir : " . implode(', ', array_slice($detectedAllergens, 0, 5)) . ".* \n\n";
                }
                $resp .= "Voici des plats **sûrs** pour vous : \n";
                foreach ($filtered as $f) {
                    $resp .= "- **" . $f['nom'] . "** (" . $f['prix'] . "€) chez " . $f['restaurant'] . "\n";
                }
                return $resp . "\n\n*Note : Toujours confirmer avec le chef via l'outil de 'Composition Personnalisée'.*";
            } else {
                return "⚠️ **Attention** : Je n'ai trouvé aucun plat 100% sûr pour vos critères actuels. Par prudence, je vous conseille de consulter le menu détaillé ou d'utiliser la **Composition Personnalisée**.";
            }
        }

        // 2. SPECIFIC RESTAURANT MENU SEARCH
        foreach ($data['restaurants'] as $rest) {
            if (strpos($m, strtolower($rest['nom'])) !== false) {
                $restMeals = array_filter($data['meals'], fn($meal) => $meal['restaurant'] === $rest['nom']);
                if (!empty($restMeals)) {
                    $resp = "🍴 **Menu chez " . $rest['nom'] . "** : \n";
                    foreach($restMeals as $rm) {
                        $resp .= "- **" . $rm['nom'] . "** (" . $rm['prix'] . "€) : " . $rm['description'] . "\n";
                    }
                    return $resp;
                }
            }
        }

        // 3. NUTRITION / CHEAPEST SEARCH
        if (strpos($m, 'protéine') !== false) {
            usort($data['meals'], fn($a, $b) => $b['proteines'] <=> $a['proteines']);
            $top = $data['meals'][0] ?? null;
            if ($top) return "💪 **Le plus protéiné** : C'est le **" . $top['nom'] . "** (" . $top['proteines'] . "g) chez " . $top['restaurant'] . " !";
        }

        if (strpos($m, 'prix') !== false || strpos($m, 'moins cher') !== false) {
            usort($data['meals'], fn($a, $b) => $a['prix'] <=> $b['prix']);
            $top = $data['meals'][0] ?? null;
            if ($top) return "💰 **Le moins cher** : Le plat **" . $top['nom'] . "** coûte seulement **" . $top['prix'] . "€** chez " . $top['restaurant'] . ".";
        }

        // 4. STATS SEARCH
        if (strpos($m, 'revenu') !== false || strpos($m, 'stat') !== false || strpos($m, 'participant') !== false) {
            return "📊 **Données Directes** : Nous avons **" . count($data['participations']) . " participants** et un revenu de **" . number_format($data['revenue'], 2) . "€**. \n\nNotre réseau compte **" . count($data['restaurants']) . " restaurants**.";
        }

        // 5. GENERIC LISTING (Improved)
        $resp = "Je suis Scout, votre assistant. Voici quelques plats que vous pourriez aimer : \n";
        $randomMeals = array_slice($data['meals'], 0, 5);
        foreach ($randomMeals as $rm) {
            $resp .= "- **" . $rm['nom'] . "** (" . $rm['prix'] . "€) chez " . $rm['restaurant'] . "\n";
        }
        return $resp . "\nPosez-moi une question plus précise sur un restaurant ou une allergie !";
    }
}

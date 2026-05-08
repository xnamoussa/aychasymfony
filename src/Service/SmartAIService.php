<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SmartAIService
 * 
 * Système de personnalisation intelligente (Local AI Simulation)
 * Fournit des recommandations basées sur l'analyse sémantique simple et la popularité.
 */
class SmartAIService
{
    private array $categories = [
        'technology' => ['tech', 'digital', 'informatique', 'ai', 'robot', 'web', 'software', 'cloud', 'dev'],
        'sport' => ['sport', 'foot', 'basket', 'tenis', 'randonnée', 'course', 'yoga', 'match', 'fitness'],
        'music' => ['concert', 'musique', 'festival', 'rock', 'jazz', 'electro', 'chant', 'instrument'],
        'business' => ['startup', 'entrepreneuriat', 'finance', 'marketing', 'networking', 'commerce', 'stratégie'],
        'food' => ['cuisine', 'dégustation', 'gastronomie', 'chef', 'restaurant', 'atelier culinaire', 'vin'],
        'education' => ['formation', 'cours', 'webinaire', 'étude', 'conférence', 'atelier', 'learning'],
        'culture' => ['musée', 'théâtre', 'exposition', 'peinture', 'histoire', 'littérature', 'cinéma'],
        'social' => ['rencontre', 'caritatif', 'bénévolat', 'solidarité', 'communauté', 'fête', 'club'],
    ];

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Analyse un événement pour extraire sa catégorie et des tags.
     */
    public function analyzeEvent(string $title, string $description): array
    {
        $text = mb_strtolower($title . ' ' . $description);
        $foundCategory = 'other';
        $maxScore = 0;
        $tags = [];

        foreach ($this->categories as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $count = mb_substr_count($text, $keyword);
                if ($count > 0) {
                    $score += $count;
                    if (!in_array($keyword, $tags)) {
                        $tags[] = $keyword;
                    }
                }
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $foundCategory = $category;
            }
        }

        return [
            'category' => $foundCategory,
            'tags' => array_slice($tags, 0, 5),
            'confidence' => $maxScore > 0 ? min(100, $maxScore * 10) : 0
        ];
    }

    /**
     * Calcule la popularité dynamique d'un événement.
     */
    public function calculatePopularity(Evenement $event): array
    {
        $views = $event->getNbVues();
        
        // On récupère le nombre de participants réels via ParticipationRepository
        $participationRepo = $this->em->getRepository(Participation::class);
        $participations = $participationRepo->findBy(['evenement' => $event]);
        
        $totalParticipants = 0;
        foreach ($participations as $p) {
            $totalParticipants += $p->getTotalParticipants() ?: 1;
        }

        // Calcul du score (pondéré)
        // Vues (x1) + Participants (x10) + Récence (bonus si récent)
        $score = ($views * 1) + ($totalParticipants * 10);
        
        $now = new \DateTime();
        $diff = $now->diff($event->getDateDebut());
        if ($diff->days < 7 && $event->getDateDebut() > $now) {
            $score += 50; // Bonus "Upcoming Soon"
        }

        $label = 'Low popularity';
        $cssClass = 'secondary';

        if ($score > 200) {
            $label = 'Trending';
            $cssClass = 'danger';
        } elseif ($score > 100) {
            $label = 'High popularity';
            $cssClass = 'warning';
        } elseif ($score > 50) {
            $label = 'Medium popularity';
            $cssClass = 'info';
        }

        return [
            'score' => $score,
            'label' => $label,
            'class' => $cssClass,
            'participants' => $totalParticipants,
            'views' => $views
        ];
    }

    /**
     * Recommande des événements basés sur le profil utilisateur.
     */
    public function recommendEvents(array $userPreferences, array $events): array
    {
        $scoredEvents = [];
        foreach ($events as $event) {
            if (!$event instanceof Evenement) continue;

            $analysis = $this->analyzeEvent($event->getTitre(), $event->getDescription());
            $similarity = $this->calculateSimilarity($userPreferences, $analysis['tags']);
            
            // On ajoute aussi un poids pour la popularité
            $pop = $this->calculatePopularity($event);
            $finalScore = ($similarity * 0.7) + (($pop['score'] / 100) * 0.3);

            $scoredEvents[] = [
                'event' => $event,
                'score' => $finalScore,
                'analysis' => $analysis,
                'popularity' => $pop
            ];
        }

        // Trier par score décroissant
        usort($scoredEvents, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredEvents;
    }

    /**
     * Calcule la similarité entre les préférences et les tags.
     */
    public function calculateSimilarity(array $userPreferences, array $eventTags): float
    {
        if (empty($userPreferences) || empty($eventTags)) {
            return 0.5; // Neutral score
        }

        $matches = array_intersect(array_map('mb_strtolower', $userPreferences), array_map('mb_strtolower', $eventTags));
        return count($matches) / max(count($userPreferences), 1);
    }

    /**
     * Simule l'apprentissage progressif en mettant à jour les préférences.
     * Dans un cas réel sans DB destructive, on peut stocker ça en session ou via une table de métadonnées existante.
     * Ici, on retourne le nouveau tableau de préférences.
     */
    public function updateUserPreferences(array $userPreferences, array $eventTags, string $interactionType): array
    {
        $weight = match ($interactionType) {
            'view' => 1,
            'like' => 3,
            'participation' => 5,
            default => 1,
        };

        foreach ($eventTags as $tag) {
            for ($i = 0; $i < $weight; $i++) {
                $userPreferences[] = $tag;
            }
        }

        // Garder les 20 derniers tags pour la pertinence
        if (count($userPreferences) > 20) {
            $userPreferences = array_slice($userPreferences, -20);
        }

        return array_unique($userPreferences);
    }
}

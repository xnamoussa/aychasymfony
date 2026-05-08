<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Participation;
use App\Entity\Post;
use App\Entity\Delivery;
use App\Entity\Favori;
use Doctrine\ORM\EntityManagerInterface;

/**
 * UserPersonaClusteringService
 * 
 * Moteur de segmentation intelligent simulant un clustering non-supervisé.
 * Analyse les interactions cross-modules pour attribuer un profil comportemental (Persona).
 */
class UserPersonaClusteringService
{
    private array $personas = [
        'L’Aventurier Sportif' => [
            'icon' => 'fas fa-mountain',
            'color' => '#ff3c36',
            'bg_class' => 'bg-danger',
            'description' => 'Toujours prêt pour une mission sur le terrain et les défis physiques.'
        ],
        'Le Gourmet Familial' => [
            'icon' => 'fas fa-utensils',
            'color' => '#ffbc00',
            'bg_class' => 'bg-warning',
            'description' => 'Passionné par la gastronomie et le partage de moments conviviaux.'
        ],
        'Le Bénévole Logistique' => [
            'icon' => 'fas fa-truck-loading',
            'color' => '#00ff88',
            'bg_class' => 'bg-success',
            'description' => 'L’expert du matériel et de l’organisation efficace.'
        ],
        'L’Explorateur Culturel' => [
            'icon' => 'fas fa-landmark',
            'color' => '#00d4ff',
            'bg_class' => 'bg-info',
            'description' => 'Avide de connaissances, d’histoire et de découvertes artistiques.'
        ],
        'Le Technophile Social' => [
            'icon' => 'fas fa-microchip',
            'color' => '#9d00ff',
            'bg_class' => 'bg-primary',
            'description' => 'Connecté, adepte des nouvelles technologies et du networking.'
        ]
    ];

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Analyse complète du comportement d'un utilisateur.
     */
    public function analyzeUserBehavior(Users $user): array
    {
        $userId = $user->getId();
        
        // 1. Boutique / Logistique
        $purchases = $this->em->getRepository(Delivery::class)->findBy(['acheteur' => $user]);
        $equipments = $user->getEquipements();
        
        // 2. Blog / Social
        $comments = $this->em->getRepository(\App\Entity\Comment::class)->findBy(['author' => $user]);
        $posts = $this->em->getRepository(Post::class)->findBy(['author' => $user]);
        
        // 3. Événements / Participations
        $participations = $this->em->getRepository(Participation::class)->findBy(['userId' => $userId]);
        
        // 4. Restauration / Favoris
        $favoris = $this->em->getRepository(Favori::class)->findBy(['user' => $user]);

        return [
            'logistique_count' => count($purchases) + count($equipments),
            'social_count' => count($comments) + count($posts),
            'event_count' => count($participations),
            'foodie_count' => count($favoris),
            'activities' => array_merge($purchases, $comments, $participations, $favoris)
        ];
    }

    /**
     * Calcule les scores pour chaque catégorie de Persona.
     */
    public function calculateBehaviorScores(array $behaviorData): array
    {
        $scores = [
            'aventure' => 0,
            'gourmet' => 0,
            'logistique' => 0,
            'culture' => 0,
            'techno' => 0
        ];

        // Logique de pondération
        $scores['logistique'] += $behaviorData['logistique_count'] * 15;
        $scores['social_count'] = $behaviorData['social_count'] * 10;
        $scores['aventure'] += $behaviorData['event_count'] * 20;
        $scores['gourmet'] += $behaviorData['foodie_count'] * 25;

        // On simule un peu de diversité basée sur les types d'activités
        foreach ($behaviorData['activities'] as $activity) {
            if ($activity instanceof Participation) {
                if ($activity->getType() === 'GROUPE') $scores['techno'] += 5;
                if ($activity->getType() === 'HEBERGEMENT') $scores['aventure'] += 10;
            }
        }

        return $scores;
    }

    /**
     * Détecte le Persona dominant à partir des scores.
     */
    public function detectPersona(array $scores): string
    {
        $maxScore = -1;
        $detected = 'Le Technophile Social'; // Par défaut

        if ($scores['aventure'] > $maxScore) {
            $maxScore = $scores['aventure'];
            $detected = 'L’Aventurier Sportif';
        }
        if ($scores['gourmet'] > $maxScore) {
            $maxScore = $scores['gourmet'];
            $detected = 'Le Gourmet Familial';
        }
        if ($scores['logistique'] > $maxScore) {
            $maxScore = $scores['logistique'];
            $detected = 'Le Bénévole Logistique';
        }
        if ($scores['culture'] > ($maxScore + 10)) { // Un peu plus dur à obtenir
            $maxScore = $scores['culture'];
            $detected = 'L’Explorateur Culturel';
        }

        return $detected;
    }

    /**
     * Génère les préférences d'affichage du Dashboard selon le Persona.
     */
    public function generateDashboardPreferences(string $persona): array
    {
        $config = $this->personas[$persona] ?? $this->personas['Le Technophile Social'];
        
        return [
            'theme_color' => $config['color'],
            'welcome_message' => "Bienvenue, " . $persona . " !",
            'priority_modules' => match($persona) {
                'L’Aventurier Sportif' => ['evenement', 'boutique'],
                'Le Gourmet Familial' => ['restauration', 'evenement'],
                'Le Bénévole Logistique' => ['boutique', 'logistique'],
                'L’Explorateur Culturel' => ['blog', 'evenement'],
                default => ['blog', 'boutique']
            }
        ];
    }

    /**
     * Génère des recommandations ciblées basées sur le Persona.
     */
    public function generateTargetedRecommendations(string $persona): array
    {
        return match($persona) {
            'L’Aventurier Sportif' => [
                'title' => 'Défis du terrain',
                'items' => ['Nouvelle Randonnée', 'Location Tentes', 'Missions Scout']
            ],
            'Le Gourmet Familial' => [
                'title' => 'Festins Partagés',
                'items' => ['Atelier Cuisine', 'Menu du Terroir', 'Dîner de Gala']
            ],
            'Le Bénévole Logistique' => [
                'title' => 'Optimisation Matériel',
                'items' => ['Gestion Inventaire', 'Nouveaux Camions', 'Kits de Survie']
            ],
            default => [
                'title' => 'Actualités IA',
                'items' => ['Nouveautés Blog', 'Prochains Meetups', 'Newsletter']
            ]
        };
    }
}

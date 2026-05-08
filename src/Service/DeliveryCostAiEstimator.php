<?php

namespace App\Service;

class DeliveryCostAiEstimator
{
    /**
     * Simule une IA qui calcule les frais de livraison.
     * Prend en compte la distance (en kilomètres), la date/heure,
     * et simule des conditions de trafic et de météo.
     *
     * @param float $distanceKm
     * @return array<string, mixed> Résultat de l'estimation avec détails
     */
    public function estimate(float $distanceKm): array
    {
        // Prix de base
        $basePrice = 5.0; // 5 Dinars de prise en charge
        
        // Coût par kilomètre (0.8 TND / km)
        $costPerKm = 0.8;
        
        // Facteurs environnementaux simulés par l'IA
        $weatherConditions = [
            ['condition' => 'Ensoleillé', 'malus' => 0.0, 'icon' => '☀️'],
            ['condition' => 'Nuageux', 'malus' => 0.0, 'icon' => '☁️'],
            ['condition' => 'Pluie légère', 'malus' => 2.0, 'icon' => '🌧️'],
            ['condition' => 'Vent fort', 'malus' => 1.5, 'icon' => '💨'],
            ['condition' => 'Orage / Forte pluie', 'malus' => 5.0, 'icon' => '⛈️']
        ];
        
        $trafficConditions = [
            ['condition' => 'Fluide', 'malus' => 0.0, 'icon' => '🟢'],
            ['condition' => 'Normal', 'malus' => 1.0, 'icon' => '🟡'],
            ['condition' => 'Dense', 'malus' => 3.5, 'icon' => '🟠'],
            ['condition' => 'Bouchons de trafic', 'malus' => 6.0, 'icon' => '🔴']
        ];

        // "IA" : générer des conditions basées de manière pseudo-aléatoire (ou juste aléatoire pour la simulation)
        $seed = crc32(date('Y-m-d H') . $distanceKm);
        srand($seed); 
        
        $weatherIndex = rand(0, count($weatherConditions) - 1);
        $trafficIndex = rand(0, count($trafficConditions) - 1);
        
        $weather = $weatherConditions[$weatherIndex];
        $traffic = $trafficConditions[$trafficIndex];

        // Majoration si c'est la nuit (de 22h à 6h)
        $hour = (int)date('H');
        $nightMalus = ($hour >= 22 || $hour <= 6) ? 4.0 : 0.0;
        
        // Calcul final
        $price = $basePrice + ($distanceKm * $costPerKm) + $weather['malus'] + $traffic['malus'] + $nightMalus;
        
        return [
            'total_tnd' => round($price, 2),
            'distance_km' => round($distanceKm, 2),
            'factors' => [
                'base' => $basePrice,
                'distance_cost' => round($distanceKm * $costPerKm, 2),
                'weather' => [
                    'label' => $weather['condition'],
                    'icon' => $weather['icon'],
                    'cost' => $weather['malus']
                ],
                'traffic' => [
                    'label' => $traffic['condition'],
                    'icon' => $traffic['icon'],
                    'cost' => $traffic['malus']
                ],
                'night_malus' => $nightMalus
            ]
        ];
    }
}

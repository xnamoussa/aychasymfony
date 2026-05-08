<?php

namespace App\Service;

/**
 * Ceci est un exemple de logique métier (Service).
 * Dans Symfony, toute classe placée dans src/Service/ est automatiquement 
 * gérée par le conteneur de services et vous pouvez l'injecter dans vos contrôleurs.
 */
class EventHelperService
{
    /**
     * Par exemple : Calcule la durée d'un événement en jours.
     */
    public function getDurationInDays(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $diff = $start->diff($end);
        return $diff->days;
    }
}

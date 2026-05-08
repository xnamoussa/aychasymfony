<?php

namespace App\Service;

class TimeAgoService
{
    public function timeAgo(?\DateTimeInterface $dateTime): string
    {
        if (!$dateTime) return '';

        $now  = new \DateTime();
        $diff = $now->diff($dateTime);

        // Convert everything to total minutes so the first check
        // does not swallow hours/days (DateInterval->i is *remaining*
        // minutes after full hours, so it is 0 for a clean "-3 hours").
        $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        $totalHours   = ($diff->days * 24) + $diff->h;

        if ($totalMinutes < 1)  return "à l'instant";
        if ($totalMinutes < 60) return $totalMinutes . " min"  . ($totalMinutes > 1 ? 's' : '') . " ago";
        if ($totalHours   < 24) return $totalHours   . " h"    . ($totalHours   > 1 ? 's' : '') . " ago";
        if ($diff->days   < 7)  return $diff->days   . " j"    . ($diff->days   > 1 ? 's' : '') . " ago";
        if ($diff->days   < 30) return floor($diff->days / 7)  . " sem"  . (floor($diff->days / 7)  > 1 ? 's' : '') . " ago";
        if ($diff->days   < 365) return floor($diff->days / 30) . " mois ago";

        return floor($diff->days / 365) . " an" . (floor($diff->days / 365) > 1 ? 's' : '') . " ago";
    }
}
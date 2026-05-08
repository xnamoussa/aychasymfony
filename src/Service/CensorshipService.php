<?php

namespace App\Service;

class CensorshipService
{
    /** @var array<string> */
    private array $badWords = [
        'merde', 'putain', 'connard', 'salaud', 'idiot',
        'imbecile', 'con', 'cul', 'fuck', 'shit', 'bitch',
        'bastard', 'asshole', 'damn', 'crap', 'nique',
        'enculer', 'pute', 'salope', 'bordel', 'chiant', 'mouheb'
    ];

    public function censorText(?string $text): string
    {
        if (!$text) return '';

        $words = explode(' ', $text);
        $result = [];

        foreach ($words as $word) {
            $clean = preg_replace('/[^a-zA-ZÀ-ÿ]/u', '', $word);
            if (in_array(strtolower($clean), $this->badWords)) {
                if (strlen($word) <= 2) {
                    $result[] = $word;
                } else {
                    $result[] = $word[0] . str_repeat('*', strlen($word) - 2) . $word[strlen($word) - 1];
                }
            } else {
                $result[] = $word;
            }
        }

        return implode(' ', $result);
    }
}

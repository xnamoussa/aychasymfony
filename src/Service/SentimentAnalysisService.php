<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentAnalysisService
{
    private HttpClientInterface $httpClient;
    private ?string $huggingFaceApiToken;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->huggingFaceApiToken = getenv('HUGGING_FACE_API_TOKEN') ?: null;
    }

    
    /** @return array<string, mixed> */
    public function analyze(string $text): array
    {
        if (!empty($this->huggingFaceApiToken)) {
            $externalResult = $this->analyzeWithHuggingFace($text);
            if ($externalResult['method'] === 'huggingface') {
                return $externalResult;
            }
        }

        return $this->analyzeWithKeywords($text);
    }

    /** @return array<string, mixed> */
    private function analyzeWithHuggingFace(string $text): array
    {
        try {
            $endpoint = 'https://api-inference.huggingface.co/models/nlptown/bert-base-multilingual-uncased-sentiment';
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'options' => [
                        'wait_for_model' => true,
                    ],
                ],
                'timeout' => 20,
            ];

            if (!empty($this->huggingFaceApiToken)) {
                $options['headers']['Authorization'] = 'Bearer ' . $this->huggingFaceApiToken;
            }

            $response = $this->httpClient->request('POST', $endpoint, $options);
            $data = $response->toArray(false);

            if (isset($data[0]) && is_array($data[0])) {
                return $this->processHuggingFaceResult($data[0]);
            }

            return [
                'score' => 0.5,
                'label' => 'neutral',
                'confidence' => 0.0,
                'method' => 'huggingface_fallback'
            ];
        } catch (\Exception $e) {
            return [
                'score' => 0.5,
                'label' => 'neutral',
                'confidence' => 0.0,
                'method' => 'huggingface_error',
                'error' => $e->getMessage()
            ];
        }
    }

    /** @return array<string, mixed> */
    private function analyzeWithKeywords(string $text): array
    {
        try {
            $normalizedText = mb_strtolower($text, 'UTF-8');

            $positiveWords = [
                'excellent', 'super', 'génial', 'parfait', 'satisfait', 'content', 'merci',
                'bravo', 'félicitations', 'succès', 'réussi', 'bon', 'bien', 'positif',
                'agréable', 'plaisir', 'heureux', 'formidable', 'magnifique', 'fantastique',
                'meilleur', 'meilleurs', 'incroyable', 'adorable', 'top', 'propre', 'efficace',
                'rapide', 'simple', 'facile', 'agréable'
            ];

            $negativeWords = [
                'mauvais', 'terrible', 'horrible', 'nul', 'déçu', 'problème', 'erreur',
                'bug', 'panne', 'cassé', 'défaut', 'mécontent', 'insatisfait', 'catastrophique',
                'désastre', 'échec', 'raté', 'lamentable', 'affreux', 'désagréable', 'lent',
                'inefficace', 'cher', 'trop', 'difficile', 'inadmissible'
            ];

            $intensifiers = [
                'très' => 1.5,
                'vraiment' => 1.4,
                'extrêmement' => 1.6,
                'absolument' => 1.5,
                'tout à fait' => 1.4,
                'tellement' => 1.4,
                'énormément' => 1.5,
                'super' => 1.2,
            ];

            $negationPatterns = [
                '/\b(?:ne|n\'t|pas|plus|jamais|aucun|sans)\b/u',
                '/\b(?:ne\s+.*?\s+pas)\b/u'
            ];

            $positiveScore = 0.0;
            $negativeScore = 0.0;
            $matches = 0;

            foreach ($positiveWords as $word) {
                $count = preg_match_all('/\b' . preg_quote($word, '/') . '\b/u', $normalizedText, $found);
                if ($count === false || $count === 0) {
                    continue;
                }

                $weight = 1.0;
                foreach ($intensifiers as $intensifier => $factor) {
                    if (preg_match('/\b' . preg_quote($intensifier, '/') . '\b\s+\b' . preg_quote($word, '/') . '\b/u', $normalizedText)) {
                        $weight = max($weight, $factor);
                    }
                }

                $negated = false;
                foreach ($negationPatterns as $pattern) {
                    if (preg_match($pattern, $normalizedText)) {
                        $negated = true;
                        break;
                    }
                }

                if ($negated) {
                    $negativeScore += $count * $weight;
                } else {
                    $positiveScore += $count * $weight;
                }

                $matches += $count;
            }

            foreach ($negativeWords as $word) {
                $count = preg_match_all('/\b' . preg_quote($word, '/') . '\b/u', $normalizedText, $found);
                if ($count === false || $count === 0) {
                    continue;
                }

                $weight = 1.0;
                foreach ($intensifiers as $intensifier => $factor) {
                    if (preg_match('/\b' . preg_quote($intensifier, '/') . '\b\s+\b' . preg_quote($word, '/') . '\b/u', $normalizedText)) {
                        $weight = max($weight, $factor);
                    }
                }

                $negated = false;
                foreach ($negationPatterns as $pattern) {
                    if (preg_match($pattern, $normalizedText)) {
                        $negated = true;
                        break;
                    }
                }

                if ($negated) {
                    $positiveScore += $count * $weight;
                } else {
                    $negativeScore += $count * $weight;
                }

                $matches += $count;
            }

            $totalWords = str_word_count($normalizedText, 0, 'àâçéèêëîïôûùüÿñæœ');

            if ($matches === 0) {
                $score = 0.5;
            } else {
                $rawScore = ($positiveScore - $negativeScore) / max($positiveScore + $negativeScore, 1);
                $score = ($rawScore + 1) / 2;
            }

            $score = min(max($score, 0.0), 1.0);
            $label = $this->getSentimentLabel($score);
            $confidence = min(1.0, ($matches / max($totalWords, 1)) * 1.2);

            return [
                'score' => round($score, 2),
                'label' => $label,
                'confidence' => round($confidence, 2),
                'positive_words' => $positiveScore,
                'negative_words' => $negativeScore,
                'method' => 'keyword_analysis'
            ];
        } catch (\Exception $e) {
            return [
                'score' => 0.5,
                'label' => 'neutral',
                'confidence' => 0.0,
                'error' => $e->getMessage(),
                'method' => 'error'
            ];
        }
    }

    /** 
     * @param array<int, array<string, mixed>> $result 
     * @return array<string, mixed>
     */
    private function processHuggingFaceResult(array $result): array
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;
        $bestScore = 0.0;
        $bestLabel = '';

        foreach ($result as $item) {
            if (!isset($item['label']) || !isset($item['score'])) {
                continue;
            }

            $score = (float) $item['score'];
            $label = $item['label'];
            $stars = 0;

            if (preg_match('/(\d+)/', $label, $matches)) {
                $stars = (int) $matches[1];
            } elseif (stripos($label, 'positive') !== false) {
                $stars = 5;
            } elseif (stripos($label, 'negative') !== false) {
                $stars = 1;
            }

            if ($stars > 0) {
                $totalScore += $stars * $score;
                $totalWeight += $score;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLabel = $label;
            }
        }

        if ($totalWeight < 0.0001) {
            return [
                'score' => 0.5,
                'label' => 'neutral',
                'confidence' => 0.0,
                'method' => 'huggingface'
            ];
        }

        $averageStars = $totalScore / $totalWeight;
        $normalizedScore = min(max(($averageStars - 1) / 4, 0.0), 1.0);
        $label = $this->getSentimentLabel($normalizedScore);

        return [
            'score' => round($normalizedScore, 2),
            'label' => $label,
            'confidence' => round($bestScore, 2),
            'method' => 'huggingface',
            'raw_label' => $bestLabel
        ];
    }

    public function getSatisfactionPercentage(float $score): int
    {
        return (int) round($score * 100);
    }

    /**
     * Convertit un score normalisé en label de sentiment
     */
    private function getSentimentLabel(float $score): string
    {
        if ($score >= 0.6) {
            return 'positive';
        } elseif ($score <= 0.4) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    /**
     * Retourne l'emoji correspondant au sentiment
     */
    public function getSentimentEmoji(float $score): string
    {
        if ($score >= 0.7) return '😊';
        if ($score <= 0.3) return '😞';
        return '😐';
    }

    /**
     * Retourne la couleur Bootstrap pour le badge
     */
    public function getSentimentColor(float $score): string
    {
        if ($score >= 0.7) return 'success';
        if ($score <= 0.3) return 'danger';
        return 'warning';
    }

    /**
     * Analyse en batch plusieurs textes
     */
    /** 
     * @param string[] $texts 
     * @return array<int, array<string, mixed>>
     */
    public function analyzeBatch(array $texts): array
    {
        $results = [];
        foreach ($texts as $text) {
            $results[] = $this->analyze($text);
            // Petit délai pour éviter de surcharger l'API gratuite
            usleep(100000); // 0.1 seconde
        }
        return $results;
    }
}
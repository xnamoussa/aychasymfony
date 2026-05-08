<?php

namespace App\Controller;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ai')]
class AIController extends AbstractController
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    #[Route('/generate-poster', name: 'app_admin_ai_generate_poster', methods: ['POST'])]
    public function generatePoster(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $type = $data['type'] ?? '';
        $description = $data['description'] ?? '';

        // On demande à Gemini de créer un prompt artistique
        $eventInfo = "Titre: $title, Type: $type, Description: $description";
        $result = $this->geminiService->generateImagePrompt($eventInfo);

        if (is_array($result) && isset($result['error'])) {
            return new JsonResponse(['error' => 'Gemini Error: ' . $result['error']], 500);
        }

        if (!$result) {
            return new JsonResponse(['error' => 'Erreur de communication avec Gemini (Réponse vide)'], 500);
        }

        // On construit l'URL Pollinations avec le prompt ultra-détaillé de Gemini

        // On construit l'URL Pollinations simplifiée et plus fiable
        $seed = rand(1, 1000000);
        $encodedPrompt = urlencode($result);
        $pollinationsUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=800&height=1200&seed={$seed}&nologo=true";


        return new JsonResponse([
            'image' => $pollinationsUrl,
            'prompt' => $result
        ]);

    }
    #[Route('/suggest-equipments', name: 'app_admin_ai_suggest_equipments', methods: ['POST'])]
    public function suggestEquipments(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $type = $data['type'] ?? '';
        $description = $data['description'] ?? '';

        $eventInfo = "Titre: $title, Type: $type, Description: $description";
        $result = $this->geminiService->generateEquipmentSuggestions($eventInfo);

        if (is_array($result) && isset($result['error'])) {
            return new JsonResponse(['error' => 'Gemini Error: ' . $result['error']], 500);
        }

        return new JsonResponse([
            'equipments' => $result
        ]);
    }

}

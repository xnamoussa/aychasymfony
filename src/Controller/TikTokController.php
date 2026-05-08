<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tiktok')]
#[IsGranted('ROLE_USER')]
class TikTokController extends AbstractController
{
    private const TIKWM_API = 'https://www.tikwm.com/api/feed/search';

    #[Route('/search', name: 'app_tiktok_search', methods: ['GET', 'POST'])]
    public function index(Request $request, HttpClientInterface $httpClient): Response
    {
        $query = $request->query->get('q') ?? $request->request->get('q', '');
        $videos = [];
        $error = null;
        $statusMessage = null;

        if (!empty($query)) {
            try {
                // Appel API TikWM (Mode sans auth)
                // Request parameters: keywords, count, cursor
                $response = $httpClient->request('GET', self::TIKWM_API, [
                    'query' => [
                        'keywords' => $query,
                        'count' => 12,
                        'cursor' => 0
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $content = $response->toArray();

                    if (isset($content['code']) && $content['code'] === 0 && isset($content['data']['videos'])) {
                        $rawVideos = $content['data']['videos'];

                        foreach ($rawVideos as $vid) {
                            $videos[] = [
                                'id' => $vid['video_id'] ?? '',
                                'title' => $vid['title'] ?? 'Viral TikTok Clip',
                                'cover' => $vid['cover'] ?? '',
                                'duration' => $vid['duration'] ?? 0,
                                'author' => $vid['author']['unique_id'] ?? 'user',
                                'play_count' => $vid['play_count'] ?? 0,
                            ];
                        }
                        
                        if (count($videos) === 0) {
                            $statusMessage = '🔇 Signal trop faible. Aucune vidéo trouvée pour : ' . $query;
                        } else {
                            $statusMessage = '✅ Synchronisation terminée. ' . count($videos) . ' fréquences virales interceptées.';
                        }
                    } else {
                        $error = $content['msg'] ?? 'Erreur inattendue depuis l\'API TikWM.';
                    }
                } else {
                    $error = '⚠️ Interférence TikWM : HTTP ' . $response->getStatusCode();
                }
            } catch (\Exception $e) {
                $error = '❌ Échec de communication : ' . $e->getMessage();
            }
        }

        return $this->render('tiktok/search.html.twig', [
            'query' => $query,
            'videos' => $videos,
            'error' => $error,
            'statusMessage' => $statusMessage,
        ]);
    }
}

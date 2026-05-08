<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'app_weather')]
    public function index(): Response
    {
        return $this->render('weather/index.html.twig', [
            'api_url' => 'https://api.open-meteo.com/v1/forecast?latitude=36.8065&longitude=10.1815&daily=sunrise,sunset&hourly=temperature_2m&forecast_days=14',
        ]);
    }
}

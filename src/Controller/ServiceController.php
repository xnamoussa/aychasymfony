<?php

namespace App\Controller;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/service')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $services = $entityManager->getRepository(Service::class)->findAll();

        return $this->render('admin_evenement/service_index.html.twig', [
            'services' => $services,
        ]);
    }
}

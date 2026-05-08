<?php
// src/Controller/HomeController.php
// Handles the root URL "/" and redirects appropriately.
// This replaces the default Symfony welcome page.

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // If not logged in, redirect to login
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Admin → dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Regular USER → Render the dedicated Home Page
        return $this->render('home/index.html.twig');
    }


}
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SessionSwitcherController extends AbstractController
{
    #[Route('/switch-role/{role}', name: 'app_switch_role')]
    public function switchRole(string $role, Request $request): Response
    {
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        
        $request->getSession()->set('current_role', $role);
        $this->addFlash('success', "Vous êtes maintenant connecté(e) en tant que: $role");
        
        return new RedirectResponse($request->headers->get('referer', $this->generateUrl('app_home')));
    }
}

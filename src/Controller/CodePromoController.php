<?php

namespace App\Controller;

use App\Entity\CodePromo;
use App\Form\CodePromoType;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/promo-codes')]
class CodePromoController extends AbstractController
{
    #[Route('/', name: 'app_admin_promo_codes_index', methods: ['GET', 'POST'])]
    public function index(Request $request, CodePromoRepository $repo, EntityManagerInterface $em): Response
    {
        $allCodes = $repo->findAll();
        
        // Handle selection or new
        $promoId = $request->query->get('id');
        $promo = $promoId ? $repo->find((int)$promoId) : new CodePromo();
        
        $form = $this->createForm(CodePromoType::class, $promo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$promo->getId()) {
                $em->persist($promo);
                $this->addFlash('success', 'Nouveau code créé avec succès!');
            } else {
                $this->addFlash('success', 'Code mis à jour!');
            }
            $em->flush();
            return $this->redirectToRoute('app_admin_promo_codes_index');
        }

        return $this->render('admin/promo_codes/index.html.twig', [
            'codes' => $allCodes,
            'form' => $form->createView(),
            'currentPromo' => $promo
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_promo_codes_delete', methods: ['POST'])]
    public function delete(Request $request, CodePromo $promo, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promo->getId(), $request->request->get('_token'))) {
            $em->remove($promo);
            $em->flush();
            $this->addFlash('danger', 'Code promo supprimé.');
        }
        return $this->redirectToRoute('app_admin_promo_codes_index');
    }
}

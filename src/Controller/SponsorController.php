<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\SponsorMailerService; 

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sponsor')]
#[IsGranted('ROLE_ADMIN')]
final class SponsorController extends AbstractController
{
    #[Route(name: 'app_sponsor_index', methods: ['GET'])]
    public function index(Request $request, SponsorRepository $sponsorRepository): Response
    {
        $filters = [
            'search'    => $request->query->get('search', ''),
            'statut'    => $request->query->get('statut', ''),
            'dateDebut' => $request->query->get('dateDebut', ''),
            'dateFin'   => $request->query->get('dateFin', ''),
            'sort'      => $request->query->get('sort', 'id'),
            'dir'       => $request->query->get('dir', 'asc'),
        ];

        $sponsors = $sponsorRepository->findWithFilters($filters);

        return $this->render('sponsor/index.html.twig', [
            'sponsors' => $sponsors,
            'filters'  => $filters,
        ]);
    }

    #[Route('/pdf', name: 'app_sponsor_pdf', methods: ['GET'])]
    public function pdf(SponsorRepository $sponsorRepository): Response
    {
        $sponsors = $sponsorRepository->findAll();

        return $this->render('sponsor/pdf.html.twig', [
            'sponsors' => $sponsors,
        ]);
    }

    #[Route('/new', name: 'app_sponsor_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SponsorMailerService $mailerService
    ): Response {
        $sponsor = new Sponsor();
        $form    = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $numLocal = $form->get('numLocal')->getData();
            $sponsor->setTelephone('+216' . $numLocal);
            $sponsor->setDateCreation(new \DateTime());

            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
                $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                $sponsor->setLogo($newFilename);
            }

            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $sponsor->setVerificationToken($token);
            $sponsor->setStatut(false); // Inactif jusqu'à vérification
            $sponsor->setEmailVerified(false);

            $entityManager->persist($sponsor);
            $entityManager->flush();

            // Envoyer l'email
            $mailerService->sendVerificationEmail($sponsor);

            $this->addFlash('success', 
                '✅ Sponsor créé ! Un email de vérification a été envoyé à ' . $sponsor->getEmail()
            );

            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_show', methods: ['GET'])]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsor_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Sponsor $sponsor,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        SponsorMailerService $mailerService
    ): Response {
        $originalEmail = $sponsor->getEmail();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $numLocal = $form->get('numLocal')->getData();
            $sponsor->setTelephone('+216' . $numLocal);

            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
                $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                $sponsor->setLogo($newFilename);
            }

            $emailChanged = $sponsor->getEmail() !== $originalEmail;
            if ($emailChanged) {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $sponsor->setVerificationToken($token);
                $sponsor->setStatut(false); // Inactif jusqu'à vérification
                $sponsor->setEmailVerified(false);
            }

            $entityManager->flush();

            if ($emailChanged) {
                // Envoyer l'email
                $mailerService->sendVerificationEmail($sponsor);

                $this->addFlash('success', 
                    '✅ Sponsor modifié ! Un email de vérification a été envoyé à ' . $sponsor->getEmail()
                );
            } else {
                $this->addFlash('success', '✅ Sponsor modifié avec succès !');
            }

            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_delete', methods: ['POST'])]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sponsor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sponsor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }

}

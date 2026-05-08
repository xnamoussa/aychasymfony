<?php

namespace App\Controller;

use App\Entity\EventSponsor;
use App\Form\EventSponsorType;
use App\Repository\EventSponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/event-sponsor')]
#[IsGranted('ROLE_ADMIN')]
final class EventSponsorController extends AbstractController
{
    #[Route(name: 'app_event_sponsor_index', methods: ['GET'])]
    public function index(Request $request, EventSponsorRepository $eventSponsorRepository): Response
    {
        $filters = [
            'search'     => $request->query->get('search', ''),
            'niveau'     => $request->query->get('niveau', ''),
            'montantMin' => $request->query->get('montantMin', ''),
            'montantMax' => $request->query->get('montantMax', ''),
            'dateDebut'  => $request->query->get('dateDebut', ''),
            'dateFin'    => $request->query->get('dateFin', ''),
            'sort'       => $request->query->get('sort', 'date'),
            'dir'        => $request->query->get('dir', 'desc'),
        ];

        $eventSponsors = $eventSponsorRepository->findWithFilters($filters);

        return $this->render('event_sponsor/index.html.twig', [
            'event_sponsors' => $eventSponsors,
            'filters'        => $filters,
        ]);
    }

    #[Route('/pdf', name: 'app_event_sponsor_pdf', methods: ['GET'])]
public function pdf(EventSponsorRepository $eventSponsorRepository): Response
{
    $all = $eventSponsorRepository->findAll();

    // Filtrer les enregistrements dont le sponsor ou l'event a été supprimé
    $eventSponsors = array_filter($all, function($es) {
        try {
            $es->getSponsor()->getNom();
            $es->getEvent()->getTitre();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    });

    return $this->render('event_sponsor/pdf.html.twig', [
        'event_sponsors' => $eventSponsors,
    ]);
}

    #[Route('/new', name: 'app_event_sponsor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $eventSponsor = new EventSponsor();
        $form = $this->createForm(EventSponsorType::class, $eventSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($eventSponsor);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_sponsor/new.html.twig', [
            'event_sponsor' => $eventSponsor,
            'form'          => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_sponsor_show', methods: ['GET'])]
    public function show(EventSponsor $eventSponsor): Response
    {
        return $this->render('event_sponsor/show.html.twig', [
            'event_sponsor' => $eventSponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_sponsor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EventSponsor $eventSponsor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventSponsorType::class, $eventSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_sponsor/edit.html.twig', [
            'event_sponsor' => $eventSponsor,
            'form'          => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_sponsor_delete', methods: ['POST'])]
    public function delete(Request $request, EventSponsor $eventSponsor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$eventSponsor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($eventSponsor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }
}
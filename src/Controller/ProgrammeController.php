<?php
namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Evenement;
use App\Form\ProgrammeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/programme')]
class ProgrammeController extends AbstractController
{
    #[Route('/new', name: 'app_programme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        $eventId = $request->query->get('event_id');
        $programme = new Programme();

        if ($eventId) {
            $event = $entityManager->getRepository(Evenement::class)->find($eventId);
            if ($event) {
                $programme->setEvent_id($event);
            }
        }

        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Force the event back if it was in the query string to prevent saving to another event by mistake
            if ($eventId && isset($event)) {
                $programme->setEvent_id($event);
            }

            $entityManager->persist($programme);
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_show', ['id' => $programme->getEvent_id()->getId_event()], Response::HTTP_SEE_OTHER);

        }

        return $this->render('programme/new.html.twig', [
            'programme' => $programme,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'app_programme_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $programmes = $entityManager->getRepository(Programme::class)->findAll();

        return $this->render('programme/index.html.twig', [
            'programmes' => $programmes,
        ]);
    }

    #[Route('/{id_prog}', name: 'app_programme_show', methods: ['GET'])]
    public function show(Programme $programme): Response
    {
        return $this->render('programme/show.html.twig', [
            'programme' => $programme,
        ]);
    }

    #[Route('/{id_prog}/edit', name: 'app_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_show', ['id' => $programme->getEvent_id()->getId_event()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('programme/edit.html.twig', [
            'programme' => $programme,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_prog}', name: 'app_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé - Réservé aux administrateurs.');
        }

        if ($this->isCsrfTokenValid('delete'.$programme->getId_prog(), $request->request->get('_token'))) {
            $eventId = $programme->getEvent_id()->getId_event();
            $entityManager->remove($programme);
            $entityManager->flush();

            if ($eventId) {
                return $this->redirectToRoute('app_evenement_show', ['id' => $eventId], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->redirectToRoute('app_programme_index', [], Response::HTTP_SEE_OTHER);
    }

}


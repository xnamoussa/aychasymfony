<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Service\TicketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Route('/tickets')]
#[IsGranted('ROLE_USER')]
class TicketController extends AbstractController
{
    private TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    // ================= READ =================
    #[Route('', name: 'app_ticket_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $items = $this->ticketService->findByUserId($user instanceof \App\Entity\Users ? $user->getId() : 0);
        } else {
            $items = $this->ticketService->findAll();
        }

        return $this->render('ticket/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/{id}/show', name: 'app_ticket_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $ticket = $this->ticketService->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket non trouvé');
        }
        return $this->render('ticket/show.html.twig', ['item' => $ticket]);
    }

    // ================= CREATE =================
    #[Route('/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->redirectToRoute('app_ticket_index');
    }

    // ================= UPDATE / EDIT =================
    #[Route('/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $ticket = $this->ticketService->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket non trouvé');
        }
        return $this->render('ticket/edit.html.twig', ['item' => $ticket]);
    }

    // ================= DELETE =================
    #[Route('/{id}/delete', name: 'app_ticket_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $ticket = $this->ticketService->find($id);
        if ($ticket && $this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $this->ticketService->delete($ticket);
            $this->addFlash('success', 'Ticket supprimé');
        }
        return $this->redirectToRoute('app_ticket_index');
    }

    // ================= MÉTIER (STUBS) =================
    #[Route('/{id}/utilise', name: 'ticket_marquer_utilise', methods: ['PATCH'])]
    public function marquerCommeUtilise(int $id): JsonResponse
    {
        $ticket = $this->ticketService->find($id);
        if ($ticket) {
            $ticket->setUsed(true);
            $ticket->setUsedAt(new \DateTime());
            $this->ticketService->save($ticket);
            return new JsonResponse(['success' => true]);
        }
        return new JsonResponse(['error' => 'Non trouvé'], 404);
    }
}
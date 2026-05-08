<?php

namespace App\Controller;

use App\Entity\ProgrammeRecommande;
use App\Repository\ProgrammeRecommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/programme-recommande')]
#[IsGranted('ROLE_USER')]
class ProgrammeRecommandeController extends AbstractController
{
    private EntityManagerInterface $em;
    private ProgrammeRecommandeRepository $repository;

    public function __construct(EntityManagerInterface $em, ProgrammeRecommandeRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    #[Route('', name: 'programme_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $programme = new ProgrammeRecommande();
        $programme->setParticipationId($data['participationId'] ?? null);
        $programme->setActivite($data['activite'] ?? '');
        $programme->setHeureDebut(new \DateTime($data['heureDebut']));
        $programme->setHeureFin(new \DateTime($data['heureFin']));
        $programme->setAmbiance($data['ambiance'] ?? 'NEUTRE');
        $programme->setJustification($data['justification'] ?? '');
        $programme->setRecommande($data['recommande'] ?? false);

        $this->em->persist($programme);
        $this->em->flush();

        return new JsonResponse($programme, 201);
    }

    #[Route('/{id}', name: 'programme_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $programme = $this->repository->find($id);
        if (!$programme) {
            return new JsonResponse(['error' => 'Programme non trouvé'], 404);
        }
        return new JsonResponse($programme);
    }

    #[Route('', name: 'programme_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->repository->findAll());
    }

    #[Route('/participation/{participationId}', name: 'programme_by_participation', methods: ['GET'])]
    public function getByParticipation(int $participationId): JsonResponse
    {
        return new JsonResponse($this->repository->findBy(['participationId' => $participationId]));
    }

    #[Route('/{id}', name: 'programme_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $programme = $this->repository->find($id);
        if (!$programme) {
            return new JsonResponse(['error' => 'Programme non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $programme->setActivite($data['activite'] ?? $programme->getActivite());
        $programme->setHeureDebut(isset($data['heureDebut']) ? new \DateTime($data['heureDebut']) : $programme->getHeureDebut());
        $programme->setHeureFin(isset($data['heureFin']) ? new \DateTime($data['heureFin']) : $programme->getHeureFin());
        $programme->setAmbiance($data['ambiance'] ?? $programme->getAmbiance());
        $programme->setJustification($data['justification'] ?? $programme->getJustification());
        $programme->setRecommande($data['recommande'] ?? $programme->isRecommande());

        $this->em->flush();
        return new JsonResponse($programme);
    }

    #[Route('/{id}', name: 'programme_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $programme = $this->repository->find($id);
        if (!$programme) return new JsonResponse(['success' => false]);

        $this->em->remove($programme);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/participation/{participationId}/delete', name: 'programme_delete_by_participation', methods: ['DELETE'])]
    public function deleteByParticipation(int $participationId): JsonResponse
    {
        $programmes = $this->repository->findBy(['participationId' => $participationId]);
        foreach ($programmes as $programme) {
            $this->em->remove($programme);
        }
        $this->em->flush();

        return new JsonResponse(['success' => true, 'deletedCount' => count($programmes)]);
    }

    #[Route('/en-cours', name: 'programme_en_cours', methods: ['GET'])]
    public function getProgrammesEnCours(): JsonResponse
    {
        return new JsonResponse($this->repository->findProgrammesEnCours());
    }

    #[Route('/a-venir', name: 'programme_a_venir', methods: ['GET'])]
    public function getProgrammesAVenir(): JsonResponse
    {
        return new JsonResponse($this->repository->findProgrammesAVenir());
    }

    #[Route('/termines', name: 'programme_termines', methods: ['GET'])]
    public function getProgrammesTermines(): JsonResponse
    {
        return new JsonResponse($this->repository->findProgrammesTermines());
    }

    #[Route('/search', name: 'programme_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $titre = $request->query->get('titre');
        $eventId = $request->query->get('eventId');
        $eventIdInt = ($eventId !== null && $eventId !== '') ? (int) $eventId : null;
        return new JsonResponse($this->repository->searchByTitreOrEvent($titre, $eventIdInt));
    }

    #[Route('/between', name: 'programme_between_dates', methods: ['GET'])]
    public function getByDateBetween(Request $request): JsonResponse
    {
        $debut = new \DateTime($request->query->get('debut'));
        $fin = new \DateTime($request->query->get('fin'));
        return new JsonResponse($this->repository->findByDateBetween($debut, $fin));
    }
}
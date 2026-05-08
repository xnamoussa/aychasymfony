<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/restaurants')]
#[IsGranted('ROLE_USER')]
class RestaurantController extends AbstractController
{
    private EntityManagerInterface $em;
    private RestaurantRepository $restaurantRepository;

    public function __construct(EntityManagerInterface $em, RestaurantRepository $restaurantRepository)
    {
        $this->em = $em;
        $this->restaurantRepository = $restaurantRepository;
    }

    #[Route('', name: 'app_restaurant_index', methods: ['GET'])]
    public function index(\App\Repository\FavoriRepository $favoriRepo): Response
    {
        $restaurants = $this->restaurantRepository->findAll();
        $user = $this->getUser();
        $favIds = [];
        if ($user) {
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            $favs = $favoriRepo->findByUser($userId);
            foreach ($favs as $f) {
                if ($f->getRestaurant()) $favIds[] = $f->getRestaurant()->getId();
            }
        }

        return $this->render('restaurant/index.html.twig', [
            'items' => $restaurants,
            'favorite_ids' => $favIds,
        ]);
    }

    #[Route('/nouveau', name: 'app_restaurant_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $restaurant = new Restaurant();
        $form = $this->createForm(\App\Form\RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($restaurant);
            $this->em->flush();

            $this->addFlash('success', 'Restaurant créé avec succès !');
            return $this->redirectToRoute('app_restaurant_index');
        }

        return $this->render('restaurant/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_restaurant_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteWeb(int $id, Request $request): Response
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->em->remove($restaurant);
            $this->em->flush();
            $this->addFlash('success', 'Restaurant supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_restaurant_index');
    }

    #[Route('/{id}/edit', name: 'app_restaurant_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editWeb(int $id, Request $request): Response
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé');
        }

        $form = $this->createForm(\App\Form\RestaurantType::class, $restaurant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Restaurant modifié avec succès !');
            return $this->redirectToRoute('app_restaurant_index');
        }

        return $this->render('restaurant/edit.html.twig', [
            'form' => $form->createView(),
            'restaurant' => $restaurant,
        ]);
    }

    #[Route('/{id}/details', name: 'app_restaurant_show', methods: ['GET'])]
    public function showWeb(int $id): Response
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé');
        }

        return $this->render('restaurant/show.html.twig', [
            'item' => $restaurant,
        ]);
    }

    #[Route('/api/{id}', name: 'restaurant_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            return new JsonResponse(['error' => 'Restaurant non trouvé'], 404);
        }
        return new JsonResponse($restaurant);
    }

    #[Route('', name: 'restaurant_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $restaurant = new Restaurant();
        $restaurant->setNom($data['nom'] ?? null);
        $restaurant->setAdresse($data['adresse'] ?? null);
        $restaurant->setTelephone($data['telephone'] ?? null);
        $restaurant->setEmail($data['email'] ?? null);
        $restaurant->setDescription($data['description'] ?? null);
        $restaurant->setImageUrl($data['imageUrl'] ?? null);
        $restaurant->setActif($data['actif'] ?? true);

        $this->em->persist($restaurant);
        $this->em->flush();

        return new JsonResponse($restaurant, 201);
    }

    #[Route('/{id}', name: 'restaurant_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            return new JsonResponse(['error' => 'Restaurant non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $restaurant->setNom($data['nom'] ?? $restaurant->getNom());
        $restaurant->setAdresse($data['adresse'] ?? $restaurant->getAdresse());
        $restaurant->setTelephone($data['telephone'] ?? $restaurant->getTelephone());
        $restaurant->setEmail($data['email'] ?? $restaurant->getEmail());
        $restaurant->setDescription($data['description'] ?? $restaurant->getDescription());
        $restaurant->setImageUrl($data['imageUrl'] ?? $restaurant->getImageUrl());
        if (isset($data['actif'])) {
            $restaurant->setActif($data['actif']);
        }

        $this->em->flush();
        return new JsonResponse($restaurant);
    }

    #[Route('/{id}', name: 'restaurant_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (!$restaurant) {
            return new JsonResponse(['success' => false]);
        }

        $this->em->remove($restaurant);
        $this->em->flush();
        return new JsonResponse(['success' => true]);
    }
}
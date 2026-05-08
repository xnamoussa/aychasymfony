<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/menu')]
#[IsGranted('ROLE_USER')]
class MenuController extends AbstractController
{
    private MenuRepository $repository;
    private EntityManagerInterface $em;
    private RestaurantRepository $restaurantRepo;
    private \App\Repository\RepasDetailleRepository $repasRepo;

    public function __construct(
        MenuRepository $repository,
        EntityManagerInterface $em,
        RestaurantRepository $restaurantRepo,
        \App\Repository\RepasDetailleRepository $repasRepo
    ) {
        $this->repository     = $repository;
        $this->em             = $em;
        $this->restaurantRepo = $restaurantRepo;
        $this->repasRepo      = $repasRepo;
    }

    // ─── ACTIONS WEB (Twig) ─────────────────────────────────────────────────

    #[Route('', name: 'app_menu_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('menu/index.html.twig', [
            'items' => $this->repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_menu_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Synchroniser restaurantNom depuis restaurantId
            $this->syncRestaurantNom($menu);

            // Handle dishesIds from custom checkbox list in template
            $dishes = $request->request->all('dishes');
            $menu->setDishesIds(array_keys($dishes));

            $this->em->persist($menu);
            $this->em->flush();

            $this->addFlash('success', 'Menu créé avec succès !');
            return $this->redirectToRoute('app_menu_index');
        }

        return $this->render('menu/new.html.twig', [
            'form' => $form->createView(),
            'allDishes' => $this->repasRepo->findAll(),
            'restaurants' => $this->restaurantRepo->findAll()
        ]);
    }

    #[Route('/{id}/show', name: 'app_menu_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $menu = $this->repository->find($id);
        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        return $this->render('menu/show.html.twig', [
            'item' => $menu,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_menu_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request): Response
    {
        $menu = $this->repository->find($id);
        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Synchroniser restaurantNom depuis restaurantId
            $this->syncRestaurantNom($menu);

            // Handle dishesIds
            $dishes = $request->request->all('dishes');
            $menu->setDishesIds(array_keys($dishes));

            $this->em->flush();
            $this->addFlash('success', 'Menu modifié avec succès !');
            return $this->redirectToRoute('app_menu_index');
        }

        return $this->render('menu/edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
            'allDishes' => $this->repasRepo->findAll(),
            'restaurants' => $this->restaurantRepo->findAll()
        ]);
    }

    #[Route('/{id}/delete', name: 'app_menu_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $request): Response
    {
        $menu = $this->repository->find($id);
        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->em->remove($menu);
            $this->em->flush();
            $this->addFlash('success', 'Menu supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_menu_index');
    }

    // ─── API JSON ───────────────────────────────────────────────────────────

    #[Route('/api/{id}', name: 'api_menu_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function apiShow(int $id): JsonResponse
    {
        $menu = $this->repository->find($id);
        if (!$menu) {
            return new JsonResponse(['error' => 'Menu non trouvé'], 404);
        }

        return new JsonResponse([
            'id'            => $menu->getId(),
            'nom'           => $menu->getNom(),
            'restaurantId'  => $menu->getRestaurantId(),
            'restaurantNom' => $menu->getRestaurantNom(),
            'prix'          => $menu->getPrix(),
            'description'   => $menu->getDescription(),
        ]);
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────

    private function syncRestaurantNom(Menu $menu): void
    {
        $restaurant = $this->restaurantRepo->find($menu->getRestaurantId());
        if ($restaurant) {
            $menu->setRestaurantNom($restaurant->getNom());
        }
    }
}
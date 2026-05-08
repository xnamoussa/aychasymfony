<?php

namespace App\Controller;

use App\Entity\Favori;
use App\Repository\FavoriRepository;
use App\Repository\RestaurantRepository;
use App\Repository\RepasDetailleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favori')]
#[IsGranted('ROLE_USER')]
class FavoriController extends AbstractController
{
    private FavoriRepository $favoriRepo;

    #[Route('/', name: 'app_favori_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $userId = method_exists($user, 'getId') ? $user->getId() : 0;
        $favs = $this->favoriRepo->findByUser($userId);
        
        $restaurants = [];
        $repas = [];

        foreach ($favs as $f) {
            if ($f->getRestaurant()) {
                $restaurants[] = $f->getRestaurant();
            }
            if ($f->getRepasDetaille()) {
                $repas[] = $f->getRepasDetaille();
            }
        }

        return $this->render('favori/index.html.twig', [
            'restaurants' => $restaurants,
            'repas' => $repas,
        ]);
    }

    public function __construct(FavoriRepository $favoriRepo)
    {
        $this->favoriRepo = $favoriRepo;
    }

    #[Route('/toggle', name: 'app_favori_toggle', methods: ['POST'])]
    public function toggle(Request $request, RestaurantRepository $restRepo, RepasDetailleRepository $repasRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;

        if (!$type || !$id) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        
        $userId = method_exists($user, 'getId') ? $user->getId() : 0;

        $criteria = ['userId' => $userId];
        $favori = null;

        if ($type === 'RESTAURANT') {
            $restaurant = $restRepo->find($id);
            if (!$restaurant) return $this->json(['error' => 'Restaurant non trouvé'], 404);
            $criteria['restaurant'] = $restaurant;
            $favori = $this->favoriRepo->findOneBy($criteria);
            
            if ($favori) {
                $this->favoriRepo->remove($favori);
                return $this->json(['active' => false, 'message' => 'Retiré des favoris']);
            } else {
                $favori = new Favori();
                $favori->setUserId($userId);
                $favori->setRestaurant($restaurant);
                $this->favoriRepo->save($favori);
                return $this->json(['active' => true, 'message' => 'Ajouté aux favoris']);
            }
        } elseif ($type === 'PLAT') {
            $repas = $repasRepo->find($id);
            if (!$repas) return $this->json(['error' => 'Plat non trouvé'], 404);
            $criteria['repasDetaille'] = $repas;
            $favori = $this->favoriRepo->findOneBy($criteria);

            if ($favori) {
                $this->favoriRepo->remove($favori);
                return $this->json(['active' => false, 'message' => 'Retiré des favoris']);
            } else {
                $favori = new Favori();
                $favori->setUserId($userId);
                $favori->setRepasDetaille($repas);
                $this->favoriRepo->save($favori);
                return $this->json(['active' => true, 'message' => 'Ajouté aux favoris']);
            }
        }

        return $this->json(['error' => 'Type invalide'], 400);
    }


    #[Route('/restaurants', name: 'app_favori_restaurants', methods: ['GET'])]
    public function listRestaurants(): Response
    {
        $user = $this->getUser();
        $userId = method_exists($user, 'getId') ? $user->getId() : 0;
        $favs = $this->favoriRepo->findByUser($userId);
        $restaurants = [];
        foreach ($favs as $f) {
            if ($f->getRestaurant()) $restaurants[] = $f->getRestaurant();
        }

        return $this->render('favori/restaurants.html.twig', [
            'items' => $restaurants,
        ]);
    }

    #[Route('/repas', name: 'app_favori_repas', methods: ['GET'])]
    public function listRepas(): Response
    {
        $user = $this->getUser();
        $userId = method_exists($user, 'getId') ? $user->getId() : 0;
        $favs = $this->favoriRepo->findByUser($userId);
        $repas = [];
        foreach ($favs as $f) {
            if ($f->getRepasDetaille()) $repas[] = $f->getRepasDetaille();
        }

        return $this->render('favori/repas.html.twig', [
            'items' => $repas,
        ]);
    }
}

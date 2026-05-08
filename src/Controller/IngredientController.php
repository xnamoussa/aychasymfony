<?php
namespace App\Controller;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ingredient')]
#[IsGranted('ROLE_USER')]
class IngredientController extends AbstractController
{
    private IngredientRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(IngredientRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    #[Route('', name: 'app_ingredient_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('ingredient/index.html.twig', [
            'items' => $this->repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ingredient_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newWeb(Request $req): Response
    {
        $ingredient = new Ingredient();
        $form = $this->createForm(\App\Form\IngredientType::class, $ingredient);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($ingredient);
            $this->em->flush();
            $this->addFlash('success', '✅ Ingrédient ajouté avec succès !');
            return $this->redirectToRoute('app_ingredient_index');
        }

        return $this->render('ingredient/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/api/new', name: 'app_ingredient_new_api', methods: ['POST'])]
    public function newApi(Request $req): Response
    {
        $data = json_decode($req->getContent(), true);

        $ingredient = new Ingredient();
        $ingredient->setNom($data['nom'] ?? null);
        $ingredient->setStockQuantite($data['quantite'] ?? 0);

        $this->em->persist($ingredient);
        $this->em->flush();

        return new Response(json_encode(['success' => true, 'id' => $ingredient->getId()]), 201, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', name: 'app_ingredient_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $ingredient = $this->repository->find($id);
        if (!$ingredient) {
            throw $this->createNotFoundException('Ingrédient non trouvé');
        }

        return $this->render('ingredient/show.html.twig', [
            'item' => $ingredient,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ingredient_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editWeb(int $id, Request $req): Response
    {
        $ingredient = $this->repository->find($id);
        if (!$ingredient) {
            throw $this->createNotFoundException('Ingrédient non trouvé');
        }

        $form = $this->createForm(\App\Form\IngredientType::class, $ingredient);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', '✏️ Ingrédient modifié avec succès !');
            return $this->redirectToRoute('app_ingredient_index');
        }

        return $this->render('ingredient/edit.html.twig', [
            'form' => $form->createView(),
            'item' => $ingredient,
        ]);
    }

    #[Route('/api/{id}/edit', name: 'app_ingredient_edit_api', methods: ['PUT'])]
    public function editApi(int $id, Request $req): Response
    {
        $ingredient = $this->repository->find($id);
        if (!$ingredient) {
            return new Response(json_encode(['error' => 'Ingrédient non trouvé']), 404, ['Content-Type' => 'application/json']);
        }

        $data = json_decode($req->getContent(), true);
        $ingredient->setNom($data['nom'] ?? $ingredient->getNom());
        $ingredient->setStockQuantite($data['quantite'] ?? $ingredient->getStockQuantite());

        $this->em->flush();

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}/delete/web', name: 'app_ingredient_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteWeb(int $id, Request $req): Response
    {
        $ingredient = $this->repository->find($id);
        if ($ingredient && $this->isCsrfTokenValid('delete'.$id, $req->request->get('_token'))) {
            $this->em->remove($ingredient);
            $this->em->flush();
            $this->addFlash('success', 'Ingrédient supprimé');
        }
        return $this->redirectToRoute('app_ingredient_index');
    }

    #[Route('/api/{id}', name: 'app_ingredient_delete_api', methods: ['DELETE'])]
    public function deleteApi(int $id): Response
    {
        $ingredient = $this->repository->find($id);
        if (!$ingredient) {
            return new Response(json_encode(['success' => false]), 404, ['Content-Type' => 'application/json']);
        }

        $this->em->remove($ingredient);
        $this->em->flush();

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/api/analyze-image', name: 'app_ingredient_analyze_image', methods: ['POST'])]
    public function analyzeImage(Request $request, \App\Service\GeminiMenuAnalyzer $gemini): Response
    {
        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['error' => 'Aucune image fournie'], 400);
        }
        
        try {
            // Extract data using Gemini
            $data = $gemini->extractMenuData($file);
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/predict-data', name: 'app_ingredient_predict_data', methods: ['POST'])]
    public function predictData(Request $request, \App\Service\GeminiMenuAnalyzer $gemini): Response
    {
        $payload = json_decode($request->getContent(), true);
        $name = $payload['nom'] ?? '';

        if (empty($name)) {
            return $this->json(['error' => 'Le nom de l\'ingrédient est requis'], 400);
        }

        try {
            $data = $gemini->predictNutritionByName($name);
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
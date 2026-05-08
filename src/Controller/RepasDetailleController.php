<?php
namespace App\Controller;
use App\Entity\RepasDetaille;use App\Form\RepasDetailleType;use App\Repository\RepasDetailleRepository;
use Doctrine\ORM\EntityManagerInterface;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/repas-detaille')]
class RepasDetailleController extends AbstractController {
    #[Route('/',name:'app_repas_detaille_index')] public function index(RepasDetailleRepository $r):Response{return $this->render('repas_detaille/index.html.twig',['items'=>$r->findAll()]);}
    
    #[Route('/catalogue',name:'app_repas_catalogue')]
    #[IsGranted('ROLE_USER')]
    public function catalogue(RepasDetailleRepository $r, \App\Repository\FavoriRepository $favoriRepo): Response
    {
        $user = $this->getUser();
        $favIds = [];
        if ($user) {
            $userId = method_exists($user, 'getId') ? $user->getId() : 0;
            $favs = $favoriRepo->findByUser($userId);
            foreach ($favs as $f) {
                if ($f->getRepasDetaille()) $favIds[] = $f->getRepasDetaille()->getId();
            }
        }

        return $this->render('repas_detaille/catalogue.html.twig', [
            'items' => $r->findAll(),
            'favorite_ids' => $favIds,
        ]);
    }

    #[Route('/composition', name: 'app_repas_composition_list')]
    #[IsGranted('ROLE_USER')]
    public function compositionList(RepasDetailleRepository $r): Response
    {
        return $this->render('repas_detaille/composition_list.html.twig', [
            'items' => $r->findAll()
        ]);
    }

    #[Route('/{id}/personnaliser', name: 'app_repas_personnaliser')]
    #[IsGranted('ROLE_USER')]
    public function personnaliser(RepasDetaille $repas, \App\Repository\IngredientRepository $ir, RepasDetailleRepository $repasRepo): Response
    {
        // Get all ingredients for customization
        $ingredients = $ir->findActifs();
        
        // Group ingredients by category
        $groupedIngredients = [];
        foreach ($ingredients as $ing) {
            $groupedIngredients[$ing->getCategorie()][] = $ing;
        }

        return $this->render('repas_detaille/personnaliser.html.twig', [
            'repas' => $repas,
            'groupedIngredients' => $groupedIngredients,
            'allItems' => $repasRepo->findAll()
        ]);
    }

    #[Route('/new', name: 'app_repas_detaille_new')]
    public function new(Request $req, EntityManagerInterface $em, \App\Repository\IngredientRepository $ir, \App\Repository\RestaurantRepository $restaur, \App\Repository\MenuRepository $mr): Response
    {
        $e = new RepasDetaille();
        $f = $this->createForm(RepasDetailleType::class, $e);
        $f->handleRequest($req);

        if ($f->isSubmitted()) {
            if ($f->isValid()) {
                // Handle file upload
                $imageFile = $f->get('imageUrl')->getData();
                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/repas',
                            $newFilename
                        );
                        $e->setImageUrl($newFilename);
                    } catch (\Exception $ex) {
                        $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                    }
                }

                $dat = $req->request->all('repas_detaille');
                if (isset($dat['ingredients'])) {
                    $e->setIngredients(json_decode($dat['ingredients'], true) ?? []);
                }
                
                // Real calculation logic
                $this->calculateNutrition($e, $ir);

                if (!empty($dat['restaurantId'])) {
                    $e->setRestaurantId((int)$dat['restaurantId']);
                }
                if (!empty($dat['menuId'])) {
                    $e->setMenuId((int)$dat['menuId']);
                }
                if (!empty($dat['tempsPreparation'])) {
                    $e->setTempsPreparation((int)$dat['tempsPreparation']);
                }

                $em->persist($e);
                $em->flush();
                $this->addFlash('success', 'Plat enregistré avec succès.');
                return $this->redirectToRoute('app_repas_detaille_index');
            } else {
                foreach ($f->getErrors(true) as $err) {
                    $this->addFlash('danger', $err->getMessage());
                }
            }
        }
        return $this->render('repas_detaille/new.html.twig', [
            'form' => $f->createView(),
            'ingredients' => $ir->findAll(),
            'restaurants' => $restaur->findAll(),
            'menus' => $mr->findAll()
        ]);
    }
    #[Route('/{id}',name:'app_repas_detaille_show',requirements:['id'=>'\d+'])] public function show(RepasDetaille $e):Response{return $this->render('repas_detaille/show.html.twig',['item'=>$e]);}
    #[Route('/{id}/edit', name: 'app_repas_detaille_edit')]
    public function edit(Request $req, RepasDetaille $e, EntityManagerInterface $em, \App\Repository\IngredientRepository $ir, \App\Repository\RestaurantRepository $restaur, \App\Repository\MenuRepository $mr): Response
    {
        $f = $this->createForm(RepasDetailleType::class, $e);
        $f->handleRequest($req);

        if ($f->isSubmitted()) {
            if ($f->isValid()) {
                // Handle file upload
                $imageFile = $f->get('imageUrl')->getData();
                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/repas',
                            $newFilename
                        );
                        $e->setImageUrl($newFilename);
                    } catch (\Exception $ex) {
                        $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                    }
                }

                $dat = $req->request->all('repas_detaille');
                if (isset($dat['ingredients'])) {
                    $e->setIngredients(json_decode($dat['ingredients'], true) ?? []);
                }
                
                // Real calculation logic
                $this->calculateNutrition($e, $ir);

                if (!empty($dat['restaurantId'])) {
                    $e->setRestaurantId((int)$dat['restaurantId']);
                }
                if (!empty($dat['menuId'])) {
                    $e->setMenuId((int)$dat['menuId']);
                }
                if (!empty($dat['tempsPreparation'])) {
                    $e->setTempsPreparation((int)$dat['tempsPreparation']);
                }

                $em->flush();
                $this->addFlash('success', 'Plat modifié avec succès.');
                return $this->redirectToRoute('app_repas_detaille_index');
            } else {
                foreach ($f->getErrors(true) as $err) {
                    $this->addFlash('danger', $err->getMessage());
                }
            }
        }

        return $this->render('repas_detaille/edit.html.twig', [
            'form' => $f->createView(),
            'item' => $e,
            'ingredients' => $ir->findAll(),
            'restaurants' => $restaur->findAll(),
            'menus' => $mr->findAll()
        ]);
    }

    private function calculateNutrition(RepasDetaille $e, \App\Repository\IngredientRepository $ir): void
    {
        $ingredientsNames = $e->getIngredients();
        $totalCalories = 0;
        $totalProteines = 0;

        foreach ($ingredientsNames as $name) {
            $ing = $ir->findOneBy(['nom' => $name]);
            if ($ing) {
                $totalCalories += $ing->getCalories() ?? 0;
                $totalProteines += $ing->getProteines() ?? 0;
            }
        }

        $e->setCalories($totalCalories);
        $e->setProteines($totalProteines);
    }
    #[Route('/{id}/delete', name: 'app_repas_detaille_delete', methods: ['POST'])]
    public function delete(Request $req, RepasDetaille $e, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $e->getId(), $req->request->get('_token'))) {
            // Manually remove dependencies in composition_menu to allow deletion
            $compositions = $em->getRepository(\App\Entity\CompositionMenu::class)->findBy(['repas' => $e]);
            foreach ($compositions as $comp) {
                $em->remove($comp);
            }
            
            $em->remove($e);
            $em->flush();
            $this->addFlash('success', 'Plat supprimé avec succès.');
        }
        return $this->redirectToRoute('app_repas_detaille_index');
    }

    #[Route('/api/analyze-image', name: 'app_repas_detaille_analyze_image', methods: ['POST'])]
    public function analyzeImage(Request $request, \App\Service\GeminiMenuAnalyzer $gemini): Response
    {
        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['error' => 'Aucune image fournie'], 400);
        }
        
        try {
            $data = $gemini->extractMenuData($file);
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}

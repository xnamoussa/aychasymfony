<?php

namespace App\Controller;

use App\Entity\Equipements;
use App\Form\EquipementsType;
use App\Repository\EquipementsRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boutique')]
#[IsGranted('ROLE_ADMIN')]
class AdminBoutiqueController extends AbstractController
{
    public function __construct(
        private readonly EquipementsRepository $equipementRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_admin_boutique_index')]
    public function index(Request $request): Response
    {
        $all = $this->equipementRepository->findAllOrderedByDateDesc();
        $qRaw = trim($request->query->getString('q'));
        $cat = $request->query->getString('categorie');
        $catFilter = $cat === '' || strcasecmp($cat, 'Toutes catégories') === 0 ? null : $cat;

        $categories = $this->collectCategories($all);
        array_unshift($categories, 'Toutes catégories');

        $equipements = $this->filterEquipements($all, $qRaw, $catFilter);
        $equipements = $this->orderEquipementsForDisplay($equipements, $catFilter);

        $livrableCount = count(array_filter($equipements, fn(Equipements $e): bool => $e->isLivrable()));
        $deliveryCount = count(array_filter($equipements, fn(Equipements $e): bool => $e->getDelivery() !== null));

        $transactions = $this->transactionRepository->findAllOrderedByDateDesc();

        return $this->render('admin_boutique/index.html.twig', [
            'equipements' => $equipements,
            'total' => count($equipements),
            'livrable_count' => $livrableCount,
            'delivery_count' => $deliveryCount,
            'categories' => $categories,
            'current_categorie' => $catFilter ?? 'Toutes catégories',
            'q' => $qRaw,
            'transactions' => $transactions,
        ]);
    }

    #[Route('/recherche', name: 'app_admin_boutique_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $all = $this->equipementRepository->findAllOrderedByDateDesc();
        $qRaw = trim($request->query->getString('q'));
        $cat = $request->query->getString('categorie');
        $catFilter = $cat === '' || strcasecmp($cat, 'Toutes catégories') === 0 ? null : $cat;

        $equipements = $this->filterEquipements($all, $qRaw, $catFilter);
        $equipements = $this->orderEquipementsForDisplay($equipements, $catFilter);

        $html = $this->renderView('admin_boutique/_equipement_rows.html.twig', [
            'equipements' => $equipements,
        ]);

        return new JsonResponse([
            'html' => $html,
            'count' => count($equipements),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_boutique_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement introuvable.');
        }

        return $this->render('admin_boutique/show.html.twig', [
            'equipement' => $equipement,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_boutique_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement introuvable.');
        }

        $form = $this->createForm(EquipementsType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Équipement modifié avec succès.');
            return $this->redirectToRoute('app_admin_boutique_show', ['id' => $equipement->getId()]);
        }

        return $this->render('admin_boutique/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier équipement',
            'equipement' => $equipement,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_boutique_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $equipement = $this->equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement introuvable.');
        }

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('delete_equipement_'.$id, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->em->remove($equipement);
        $this->em->flush();
        $this->addFlash('success', 'Équipement supprimé.');

        return $this->redirectToRoute('app_admin_boutique_index');
    }

    /**
     * @param Equipements[] $all
     * @return Equipements[]
     */
    private function filterEquipements(array $all, string $qRaw, ?string $catFilter): array
    {
        return array_values(array_filter($all, function (Equipements $e) use ($qRaw, $catFilter) {
            $matchSearch = true;
            if ($qRaw !== '') {
                $haystack = mb_strtolower($e->getNom() . ' ' . $e->getDescription() . ' ' . $e->getCategorie(), 'UTF-8');
                $matchSearch = str_contains($haystack, mb_strtolower($qRaw, 'UTF-8'));
            }
            $matchCat = $catFilter === null || strcasecmp(trim($e->getCategorie() ?? ''), trim($catFilter)) === 0;
            return $matchSearch && $matchCat;
        }));
    }

    /**
     * @param Equipements[] $list
     * @return Equipements[]
     */
    private function orderEquipementsForDisplay(array $list, ?string $catFilter): array
    {
        usort($list, function (Equipements $a, Equipements $b) {
            return $b->getDateAjout() <=> $a->getDateAjout();
        });
        return $list;
    }

    /**
     * @param Equipements[] $all
     * @return string[]
     */
    private function collectCategories(array $all): array
    {
        $set = [];
        foreach ($all as $e) {
            $c = $e->getCategorie();
            if ($c !== null && trim($c) !== '') {
                $set[mb_strtolower(trim($c))] = trim($c);
            }
        }
        $list = array_values($set);
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        return $list;
    }
}

<?php

namespace App\Controller;

use App\Entity\Restauration;
use App\Entity\ParticipationRestaurant;
use App\Repository\RestaurationRepository;
use App\Repository\ParticipationRestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTimeImmutable;

#[IsGranted('ROLE_USER')]
class RestaurationController extends AbstractController
{
    private EntityManagerInterface $em;
    private RestaurationRepository $restaurationRepo;
    private ParticipationRestaurantRepository $participantRepo;

    public function __construct(
        EntityManagerInterface $em,
        RestaurationRepository $restaurationRepo,
        ParticipationRestaurantRepository $participantRepo
    ) {
        $this->em = $em;
        $this->restaurationRepo = $restaurationRepo;
        $this->participantRepo = $participantRepo;
    }

    #[Route('/admin/restauration/dashboard', name: 'app_restauration_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(): Response
    {
        return $this->render('restauration/dashboard.html.twig', [
            'total_menus' => count($this->getMenusActifs()),
            'total_options' => count($this->getAllOptions()),
        ]);
    }

    // ================= MENU =================
    public function createMenu(string $nom, ?int $optionRestaurationId, bool $actif = true): Restauration
    {
        $menu = Restauration::menu($nom, $optionRestaurationId, $actif);

        $this->em->persist($menu);
        $this->em->flush();

        return $menu;
    }

    /** @return Restauration[] */
    public function getMenusActifs(): array
    {
        return $this->restaurationRepo->findBy([
            'type' => Restauration::TYPE_MENU,
            'actif' => true,
        ]);
    }

    /** @return Restauration[] */
    public function getMenusByOption(int $optionId): array
    {
        return array_filter($this->getMenusActifs(), function (Restauration $menu) use ($optionId) {
            return $menu->getOptionRestaurationId() === $optionId;
        });
    }

    public function updateMenu(Restauration $menu): Restauration
    {
        $menu->setType(Restauration::TYPE_MENU);
        $this->em->flush();
        return $menu;
    }

    public function deleteMenu(int $id): bool
    {
        $menu = $this->restaurationRepo->find($id);
        if (!$menu || $menu->getType() !== Restauration::TYPE_MENU) {
            return false;
        }

        $this->em->remove($menu);
        $this->em->flush();
        return true;
    }

    // ================= OPTION =================
    public function createOption(string $libelle, string $typeEvenement, bool $actif = true): Restauration
    {
        $option = Restauration::option($libelle, $typeEvenement, $actif);

        $this->em->persist($option);
        $this->em->flush();

        return $option;
    }

    /** @return Restauration[] */
    public function getOptionsByType(string $type): array
    {
        return $this->restaurationRepo->findBy([
            'typeEvenement' => $type,
            'type' => Restauration::TYPE_OPTION,
        ]);
    }

    /** @return Restauration[] */
    public function getAllOptions(): array
    {
        return array_filter($this->restaurationRepo->findBy(['type' => Restauration::TYPE_OPTION]), fn($r) => $r->isActif());
    }

    // ================= REPAS =================
    public function createRepas(string $nomRepas, float $prix, DateTimeImmutable $date, int $participantId): Restauration
    {
        $repas = Restauration::repas($nomRepas, $prix, $date, $participantId);

        $this->em->persist($repas);
        $this->em->flush();

        return $repas;
    }

    /** @return Restauration[] */
    public function getRepasByParticipant(int $participantId): array
    {
        return $this->restaurationRepo->findBy(['participantId' => $participantId, 'type' => Restauration::TYPE_REPAS]);
    }

    /** @return Restauration[] */
    public function getRepasByDate(DateTimeImmutable $date): array
    {
        return $this->restaurationRepo->findBy(['date' => $date, 'type' => Restauration::TYPE_REPAS]);
    }

    public function hasRepasForDay(int $participantId, DateTimeImmutable $date): bool
    {
        return !empty($this->getRepasByParticipant($participantId)) && !empty($this->getRepasByDate($date));
    }

    public function updateRepas(Restauration $repas): Restauration
    {
        $repas->setType(Restauration::TYPE_REPAS);
        $this->em->flush();
        return $repas;
    }

    public function deleteRepas(int $id): bool
    {
        $repas = $this->restaurationRepo->find($id);
        if (!$repas || $repas->getType() !== Restauration::TYPE_REPAS) {
            return false;
        }
        $this->em->remove($repas);
        $this->em->flush();
        return true;
    }

    // ================= RESTRICTION =================
    public function createRestriction(string $libelle, string $description, bool $actif = true): Restauration
    {
        $restriction = Restauration::restriction($libelle, $description, $actif);
        $this->em->persist($restriction);
        $this->em->flush();
        return $restriction;
    }

    /** @return Restauration[] */
    public function getRestrictionsActives(): array
    {
        return $this->restaurationRepo->findBy(['type' => Restauration::TYPE_RESTRICTION, 'actif' => true]);
    }

    // ================= PRESENCE =================
    public function createPresence(int $participantId, DateTimeImmutable $date, bool $abonnementActif = true): Restauration
    {
        $presence = Restauration::presence($participantId, $date, $abonnementActif);
        $this->em->persist($presence);
        $this->em->flush();
        return $presence;
    }

    /** @return Restauration[] */
    public function getPresenceByParticipant(int $participantId): array
    {
        return $this->restaurationRepo->findBy(['participantId' => $participantId, 'type' => Restauration::TYPE_PRESENCE]);
    }

    public function hasPresenceForDay(int $participantId, DateTimeImmutable $date): bool
    {
        return !empty(array_filter($this->getPresenceByParticipant($participantId), fn(Restauration $p) => $p->getDatePresence() == $date));
    }

    public function isAbonnementActif(int $participantId): bool
    {
        return !empty(array_filter($this->getPresenceByParticipant($participantId), fn(Restauration $p) => $p->isAbonnementActif()));
    }

    // ================= BESOIN PARTICIPANT =================
    public function createBesoin(ParticipationRestaurant $besoin): ParticipationRestaurant
    {
        $this->em->persist($besoin);
        $this->em->flush();
        return $besoin;
    }

    /** @return ParticipationRestaurant[] */
    public function getBesoinsByParticipantId(int $participantId): array
    {
        return $this->participantRepo->findBy(['participantId' => $participantId]);
    }
}
<?php

namespace App\Controller;

use App\Entity\SponsorFeedback;
use App\Repository\SponsorRepository;
use App\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\SponsorFeedbackRepository;

class FrontSponsorController extends AbstractController
{
    #[Route('/sponsors', name: 'app_front_sponsors', methods: ['GET'])]
    public function index(
        SponsorRepository $sponsorRepository,
        SponsorFeedbackRepository $feedbackRepository
    ): Response {
        return $this->render('front_sponsors/index.html.twig', [
            'sponsors'  => $sponsorRepository->findAll(),
            'feedbacks' => $feedbackRepository->findAllFeedbacks(),
        ]);
    }

    // Step 1: Check if nom + email match a sponsor
    #[Route('/sponsor/check', name: 'front_sponsor_check', methods: ['POST'])]
    public function checkSponsor(Request $request, SponsorRepository $sponsorRepository): JsonResponse
    {
        $nom   = trim($request->request->get('nom', ''));
        $email = trim($request->request->get('email', ''));

        $sponsor = $sponsorRepository->findOneBy([
            'nom'   => $nom,
            'email.value' => $email,
        ]);

        if ($sponsor) {
            return new JsonResponse(['success' => true, 'sponsorId' => $sponsor->getId()]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Aucun sponsor trouvé avec ces informations.']);
    }

    // Step 2: Submit feedback or report
    #[Route('/sponsor/feedback', name: 'front_sponsor_feedback', methods: ['POST'])]
    public function submitFeedback(
        Request $request,
        SponsorRepository $sponsorRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $sponsorId = $request->request->get('sponsorId');
        $type      = $request->request->get('type'); // 'feedback' or 'report'
        $contenu   = trim($request->request->get('contenu', ''));
        $nom       = trim($request->request->get('nom', ''));
        $email     = trim($request->request->get('email', ''));

        if (!in_array($type, ['feedback', 'report']) || empty($contenu)) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.']);
        }

        $sponsor = $sponsorRepository->find($sponsorId);
        if (!$sponsor) {
            return new JsonResponse(['success' => false, 'message' => 'Sponsor introuvable.']);
        }

        $feedback = new SponsorFeedback();
        $feedback->setType($type);
        $feedback->setNom($nom);
        $feedback->setEmail(new Email($email));
        $feedback->setContenu($contenu);
        $feedback->setSponsor($sponsor);

        $em->persist($feedback);
        $em->flush();

        $message = $type === 'feedback'
            ? 'Merci pour votre feedback !'
            : 'Votre signalement a été envoyé.';

        return new JsonResponse(['success' => true, 'message' => $message, 'type' => $type, 'feedbackId' => $feedback->getId()]);
    }

    #[Route('/sponsor/feedback/update', name: 'front_sponsor_feedback_update', methods: ['POST'])]
    public function updateFeedback(
        Request $request,
        SponsorFeedbackRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $feedbackId = $request->request->get('feedbackId');
        $contenu    = trim($request->request->get('contenu', ''));
        $email      = trim($request->request->get('email', ''));

        $feedback = $repo->find($feedbackId);
        if (!$feedback || $feedback->getEmail()->getValue() !== $email) {
            return new JsonResponse(['success' => false, 'message' => 'Feedback introuvable ou vous n\'êtes pas l\'auteur.']);
        }

        if (empty($contenu)) {
            return new JsonResponse(['success' => false, 'message' => 'Le contenu ne peut pas être vide.']);
        }

        $feedback->setContenu($contenu);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Feedback mis à jour avec succès.']);
    }

    #[Route('/sponsor/feedback/delete', name: 'front_sponsor_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(
        Request $request,
        SponsorFeedbackRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $feedbackId = $request->request->get('feedbackId');
        $email      = trim($request->request->get('email', ''));

        $feedback = $repo->find($feedbackId);
        if (!$feedback || $feedback->getEmail()->getValue() !== $email) {
            return new JsonResponse(['success' => false, 'message' => 'Feedback introuvable ou vous n\'êtes pas l\'auteur.']);
        }

        $em->remove($feedback);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Feedback supprimé avec succès.']);
    }

    #[Route('/verify/{token}', name: 'sponsor_verify_email', methods: ['GET'])]
    public function verifyEmail(
        string $token,
        SponsorRepository $sponsorRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $sponsor = $sponsorRepository->findOneBy(['verificationToken' => $token]);

        if (!$sponsor) {
            $this->addFlash('danger', '❌ Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_home'); // fixed to app_home instead of front_home
        }

        // Activer le sponsor
        $sponsor->setStatut(true);
        $sponsor->setVerificationToken(null); // Invalider le token
        $entityManager->flush();

        return $this->render('sponsor/email_verified.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }
}

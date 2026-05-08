<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ReservationMaquillage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ReservationMaquillageController extends AbstractController
{
    #[Route('/evenement/{id}/reserver-makeup', name: 'app_evenement_reserver_makeup', methods: ['POST'])]
    public function reserver(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if (!$evenement->isProposeMakeup()) {
            $this->addFlash('error', 'Cet événement ne propose pas de coin Make-up.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId_event()]);
        }

        $emailAddress = $request->request->get('email');

        if (empty($emailAddress) || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Veuillez fournir une adresse e-mail valide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId_event()]);
        }

        $reservation = new ReservationMaquillage();
        $reservation->setEvent($evenement);
        $reservation->setEmail($emailAddress);
        $reservation->setCreatedAt(new \DateTime());

        $entityManager->persist($reservation);
        $entityManager->flush();

        try {
            $email = (new Email())
                ->from('feryellamouchi@gmail.com')
                ->to($emailAddress)
                ->subject('Confirmation de réservation: Coin Make-up')
                ->html('<p>Bonjour,</p><p>Votre réservation pour le coin Make-up à l\'événement <strong>' . htmlspecialchars($evenement->getTitre()) . '</strong> a bien été enregistrée.</p><p>Merci et à très bientôt !</p>');

            $mailer->send($email);
            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès. Un e-mail de confirmation vous a été envoyé.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Votre réservation est enregistrée, mais une erreur est survenue lors de l\'envoi de l\'e-mail de confirmation.');
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId_event()]);
    }
}

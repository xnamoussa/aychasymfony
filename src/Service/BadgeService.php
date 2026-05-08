<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Participation;
use App\Repository\EvenementRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Twig\Environment;

class BadgeService
{
    private Environment $twig;
    private EvenementRepository $evenementRepository;

    public function __construct(Environment $twig, EvenementRepository $evenementRepository)
    {
        $this->twig = $twig;
        $this->evenementRepository = $evenementRepository;
    }

    public function generateBadgePdf(Participation $participation, ?string $participantName = null): string
    {
        // 1. Fetch Event Info
        $evenement = null;
        if ($participation->getEvenementId()) {
            $evenement = $this->evenementRepository->find($participation->getEvenementId());
        }
        
        // 2. Generate QR Code
        // Format requested: Human readable info + Technical Code TKT-ID-TIMESTAMP-RANDOM
        $ticketCode = "TKT-" . $participation->getId() . "-" . time() . bin2hex(random_bytes(3));
        
        $qrContent = sprintf(
            "COORDONNÉES PASS\nNom: %s\nÉvénement: %s\nTicket: %s\nDate: %s",
            $participantName ?? ('Participant #' . $participation->getUserId()),
            $evenement ? $evenement->getTitre() : 'N/A',
            $ticketCode,
            $participation->getDateInscription()->format('d/m/Y')
        );
        
        $qrCode = new QrCode(
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeData = $result->getDataUri();

        // 3. Generate Barcode (Code128 - SVG)
        $generator = new BarcodeGeneratorSVG();
        $barcodeUri = "data:image/svg+xml;base64," . base64_encode($generator->getBarcode($ticketCode, $generator::TYPE_CODE_128, 3, 100));

        // 4. Render HTML Template
        $html = $this->twig->render('participation/badge_pdf.html.twig', [
            'p' => $participation,
            'event' => $evenement,
            'participantName' => $participantName ?? ('Participant #' . $participation->getUserId()),
            'qrCode' => $qrCodeData,
            'barcode' => $barcodeUri,
            'qrText' => $ticketCode, // Use the technical code here for display
            'generatedAt' => new \DateTime(),
        ]);

        // 5. Configure Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateAbonnementBadgePdf(Abonnement $abonnement, ?string $participantName = null): string
    {
        // 1. Prepare Data
        $passCode = "PASS-" . $abonnement->getId() . "-" . time() . bin2hex(random_bytes(3));
        
        $qrContent = sprintf(
            "COORDONNÉES ABONNEMENT\nNom: %s\nType: %s\nPass: %s\nDate: %s",
            $participantName ?? ($abonnement->getUserName() ?? 'Utilisateur #' . $abonnement->getUserId()),
            $abonnement->getNom() ?? 'Pack Abonnement',
            $passCode,
            $abonnement->getDateDebut() ? $abonnement->getDateDebut()->format('d/m/Y') : 'N/A'
        );
        
        $qrCode = new QrCode(
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeData = $result->getDataUri();

        $generator = new BarcodeGeneratorSVG();
        $barcodeUri = "data:image/svg+xml;base64," . base64_encode($generator->getBarcode($passCode, $generator::TYPE_CODE_128, 3, 100));

        // 2. Render Template (Reusing the same responsive template but with Abonnement data)
        // We simulate a Participation-like structure for the template to reuse the CSS
        $html = $this->twig->render('participation/badge_pdf.html.twig', [
            'p' => (object)[
                'id' => $abonnement->getId(),
                'statut' => $abonnement->getStatut(),
                'totalParticipants' => 1,
                'montantCalcule' => $abonnement->getPrix(),
                'userId' => $abonnement->getUserId(),
                'type' => 'Abonnement',
                'dateInscription' => $abonnement->getDateDebut(),
            ],
            'event' => (object)[
                'titre' => $abonnement->getNom() ?? 'Pack Abonnement',
                'dateDebut' => $abonnement->getDateDebut(),
                'lieu' => 'Accès Global LAMMA',
            ],
            'participantName' => $participantName ?? ($abonnement->getUserName() ?? 'Utilisateur #' . $abonnement->getUserId()),
            'qrCode' => $qrCodeData,
            'barcode' => $barcodeUri,
            'qrText' => $passCode, // Technical code
            'generatedAt' => new \DateTime(),
        ]);

        // 3. Render PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateFacturePdf(Abonnement $abonnement, string $participantName): string
    {
        $invoiceNumber = "INV-" . date('Y') . "-" . str_pad((string)$abonnement->getId(), 4, '0', STR_PAD_LEFT);

        $html = $this->twig->render('abonnement/facture_pdf.html.twig', [
            'abonnement' => $abonnement,
            'participantName' => $participantName,
            'invoiceNumber' => $invoiceNumber,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

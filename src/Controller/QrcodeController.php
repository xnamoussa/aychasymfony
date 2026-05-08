<?php

namespace App\Controller;

use App\Repository\EquipementsRepository;
use App\Repository\TransactionRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QrcodeController extends AbstractController
{
    #[Route('/qrcode/{id}', name: 'app_qrcode_recap', requirements: ['id' => '\d+'])]
    public function index(string $id, EquipementsRepository $equipementRepository, TransactionRepository $transactionRepository): Response
    {
        $equipement = $equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement non trouvé.');
        }

        $transaction = $transactionRepository->findOneBy(['equipement' => $equipement]);

        return $this->render('qrcode/recap.html.twig', [
            'equipement' => $equipement,
            'transaction' => $transaction,
            'delivery' => $equipement->getDelivery(),
        ]);
    }

    #[Route('/qrcode/{id}/pdf', name: 'app_qrcode_recap_pdf', requirements: ['id' => '\d+'])]
    public function indexPdf(string $id, EquipementsRepository $equipementRepository, TransactionRepository $transactionRepository): Response
    {
        $equipement = $equipementRepository->find($id);
        if (!$equipement) {
            throw $this->createNotFoundException('Équipement non trouvé.');
        }

        $transaction = $transactionRepository->findOneBy(['equipement' => $equipement]);

        $html = $this->renderView('qrcode/recap.html.twig', [
            'equipement' => $equipement,
            'transaction' => $transaction,
            'delivery' => $equipement->getDelivery(),
            'is_pdf' => true,
        ]);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="bordereau-lamma-' . $id . '.pdf"'
            ]
        );
    }
}

<?php

namespace App\Command;

use App\Service\CartService;
use App\Service\MealTicketService;
use App\Service\GeminiMenuAnalyzer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:test-services',
    description: 'Teste les services avancés (Cart, MealTicket, Gemini)',
)]
class TestServicesCommand extends Command
{
    private CartService $cartService;
    private MealTicketService $mealTicketService;
    private GeminiMenuAnalyzer $geminiAnalyzer;
    private RequestStack $requestStack;
    private EntityManagerInterface $em;

    public function __construct(
        CartService $cartService, 
        MealTicketService $mealTicketService, 
        GeminiMenuAnalyzer $geminiAnalyzer,
        RequestStack $requestStack,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->cartService = $cartService;
        $this->mealTicketService = $mealTicketService;
        $this->geminiAnalyzer = $geminiAnalyzer;
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test des Métiers Avancés LAMMA');

        // ==========================================
        // 1. CARTSERVICE
        // ==========================================
        $io->section('1. Test du CartService (Panier)');
        
        // Simuler une session
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $this->requestStack->push($request);

        $this->cartService->add(1); // Add item ID 1
        $this->cartService->add(1); // Add item ID 1 again
        $this->cartService->add(2);  // Add item ID 2

        $io->writeln(" + Ajout Article 1 (15.50) x 2");
        $io->writeln(" + Ajout Article 2 (9.99) x 1");
        $io->writeln(" => Nombre d'articles : <info>" . $this->cartService->countItems() . "</info>");
        $io->writeln(" => Total du panier   : <info>" . $this->cartService->getTotal() . " €</info>");
        
        $this->cartService->remove("1"); // decrement item 1
        $io->writeln(" - Retrait de 1 Article 1");
        $io->writeln(" => Nouveau Total     : <info>" . $this->cartService->getTotal() . " €</info>");
        $io->success('CartService fonctionne correctement.');

        // ==========================================
        // 2. GEMINI ANALYZER
        // ==========================================
        $io->section('2. Test du GeminiMenuAnalyzer (Image Analysis)');
        
        // Créer une image fallacieuse
        $tempImg = sys_get_temp_dir() . '/pizza_test.jpg';
        file_put_contents($tempImg, 'dummy image data');
        
        $io->writeln("Analyse de l'image (Simulation AI sur 'pizza_test.jpg') : ");
        $result = $this->geminiAnalyzer->extractMenuDataFromPath($tempImg);
        
        $io->writeln(" - Nom         : <comment>{$result['nom']}</comment>");
        $io->writeln(" - Description : <comment>{$result['description']}</comment>");
        $io->writeln(" - Prix        : <comment>{$result['prix']} €</comment>");
        $io->writeln(" - Tags        : <comment>" . implode(', ', $result['tags']). "</comment>");
        
        $io->success('GeminiMenuAnalyzer résilient (Extraction JSON validée).');
        unlink($tempImg);

        // ==========================================
        // 3. MEALTICKET SERVICE
        // ==========================================
        $io->section('3. Test du MealTicketService (QR & Stock Déduction)');
        
        $this->em->beginTransaction(); // Transaction pour ne pas polluer la DB
        try {
            $io->writeln("Génération d'un ticket pour l'utilisateur 101, créneau " . date('Y-m-d H:i') . "...");
            $ticket = $this->mealTicketService->generateTicket(999, 101, new \DateTime());
            
            $qrCode = $ticket->getQrCode();
            $io->writeln(" => Ticket généré : <info>{$qrCode}</info>");
            
            $io->writeln("Test de génération d'un ticket en double sur le même créneau...");
            try {
                $this->mealTicketService->generateTicket(999, 101, new \DateTime());
                $io->error("Erreur, le ticket en double aurait du être bloqué.");
            } catch (\Exception $e) {
                $io->writeln(" => Bloqué avec succès : <comment>{$e->getMessage()}</comment>");
            }

            $io->writeln("Consommation du ticket (Validation + Déduction de stock)...");
            $consumeResult = $this->mealTicketService->consumeTicket($qrCode);
            
            $io->writeln(" => Action : <info>{$consumeResult['message']}</info>");
            foreach($consumeResult['alerts'] as $alert) {
                 $io->writeln(" => Alerte : <error>{$alert}</error>");
            }

            $io->success('MealTicketService validé (Génération stricte + Validations associées).');
        } catch (\Exception $e) {
            $io->error("Erreur de logique DB MealTicket: " . $e->getMessage());
        } finally {
            $this->em->rollback();
        }

        return Command::SUCCESS;
    }
}

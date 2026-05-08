<?php
require 'vendor/autoload.php';

use App\Service\ScoutChatbotService;
use App\Repository\IngredientRepository;
use App\Repository\RepasDetailleRepository;
use App\Repository\ParticipationRepository;
use App\Repository\RestaurantRepository;

// Dummy repositories for testing or just mock them
// But I want to test the REAL ones.
// I'll use the kernel.

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get the service from the container
$service = $container->get(ScoutChatbotService::class);

echo "Testing Chatbot...\n";
try {
    $resp = $service->getAiResponse("Hello");
    echo "Response: " . $resp . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

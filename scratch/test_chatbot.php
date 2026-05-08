<?php
require_once __DIR__ . '/vendor/autoload_runtime.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/.env');

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $request = Request::create('/api/chatbot/ask', 'POST', [], [], [], [], json_encode(['message' => 'test']));
    $response = $kernel->handle($request);
    
    echo "STATUS: " . $response->getStatusCode() . "\n";
    echo "CONTENT: " . $response->getContent() . "\n";
    
    return $response;
};

<?php

require 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\FaceData;
use App\Entity\Users;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

try {
    $faceData = new FaceData();
    $faceData->setEmail('test_new_enroll@example.com');
    // random dummy descriptor
    $dummyDescriptor = array_fill(0, 128, 0.123);
    $faceData->setFaceDescriptor($dummyDescriptor);

    $em->persist($faceData);
    $em->flush();
    echo "FaceData persisted successfully!\n";
} catch (\Exception $e) {
    echo "Error inserting FaceData: " . $e->getMessage() . "\n";
}

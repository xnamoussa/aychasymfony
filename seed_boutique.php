<?php

use App\Entity\Equipements;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

$ur = $em->getRepository(Users::class);
$demoUser = $ur->findOneBy(['email' => 'demo@lamma.tn']);

if (!$demoUser) {
    $demoUser = new Users();
    $demoUser->setEmail('demo@lamma.tn');
    $demoUser->setName('Demo User');
    $demoUser->setRoles(['ROLE_USER']);
    $demoUser->setPassword('demo123'); // Plain for this context
    $em->persist($demoUser);
    $em->flush();
}

$items = [
    ['Tente Husky Fighter', 'Tente 4 saisons robuste pour expéditions extrêmes.', 'Tentes', 850.00, 'Tunis'],
    ['Sac de Couchage Valandré', 'Duvet d\'oie haute qualité, confort -15°C.', 'Couchage', 1200.00, 'Ariana'],
    ['Réchaud Jetboil Flash', 'Système de cuisson rapide 1L en 100 secondes.', 'Cuisine', 350.00, 'Sousse'],
    ['Sac à dos Osprey Aether 70', 'Confort de portage exceptionnel pour trekkings longs.', 'Sacs', 680.00, 'Bizerte'],
];

foreach ($items as $item) {
    $e = new Equipements();
    $e->setNom($item[0]);
    $e->setDescription($item[1]);
    $e->setCategorie($item[2]);
    $e->setPrix($item[3]);
    $e->setVille($item[4]);
    $e->setOwner($demoUser);
    $e->setDateAjout(new \DateTimeImmutable());
    $e->setStatut('DISPONIBLE');
    $e->setLivrable(true);
    $em->persist($e);
}

$em->flush();
echo "Successfully seeded " . count($items) . " items.\n";

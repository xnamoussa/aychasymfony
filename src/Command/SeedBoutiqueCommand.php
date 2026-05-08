<?php

namespace App\Command;

use App\Entity\Equipements;
use App\Entity\Users;
use App\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-boutique')]
class SeedBoutiqueCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ur = $this->em->getRepository(Users::class);
        $demoUser = $ur->findOneBy(['email.value' => 'demo@lamma.tn']);

        if (!$demoUser) {
            $demoUser = new Users();
            $demoUser->setEmail(new Email('demo@lamma.tn'));
            $demoUser->setName('Demo User');
            $demoUser->setRole('USER');
            $demoUser->setPassword('demo123');
            $this->em->persist($demoUser);
            $this->em->flush();
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
            $e->setPrix((string)$item[3]);
            $e->setVille($item[4]);
            $e->setType('Vente');
            $e->setOwner($demoUser);
            $e->setDateAjout(new \DateTime()); // Mutable
            $e->setStatut('DISPONIBLE');
            $e->setLivrable(true);
            $this->em->persist($e);
        }

        $this->em->flush();
        $output->writeln("Successfully seeded Boutique data!");
        return Command::SUCCESS;
    }
}

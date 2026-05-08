<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class GenericService
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function save(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function delete(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }
}

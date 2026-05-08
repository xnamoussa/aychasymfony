<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity]
#[ORM\Table(name: "favori", options: ["engine" => "InnoDB"])]
#[ORM\HasLifecycleCallbacks]
class Favori
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private Users $user;

    #[ORM\ManyToOne(targetEntity: Restaurant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToOne(targetEntity: RepasDetaille::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?RepasDetaille $repasDetaille = null;



    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): Users
    {
        return $this->user;
    }

    public function setUser(Users $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;
        return $this;
    }

    public function getRepasDetaille(): ?RepasDetaille
    {
        return $this->repasDetaille;
    }

    public function setRepasDetaille(?RepasDetaille $repasDetaille): static
    {
        $this->repasDetaille = $repasDetaille;
        return $this;
    }



}

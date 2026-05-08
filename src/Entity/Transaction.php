<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use App\Entity\Users;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'payment_transaction')]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Equipements::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Equipements $equipement;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Users $seller;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Users $buyer;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(length: 255, nullable: true)]
    // #[ \Symfony\Component\Serializer\Attribute\Ignore]
    private ?string $stripeToken = null;



    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipement(): Equipements
    {
        return $this->equipement;
    }

    public function setEquipement(Equipements $equipement): static
    {
        $this->equipement = $equipement;
        return $this;
    }

    public function getSeller(): Users
    {
        return $this->seller;
    }

    public function setSeller(Users $seller): static
    {
        $this->seller = $seller;
        return $this;
    }

    public function getBuyer(): Users
    {
        return $this->buyer;
    }

    public function setBuyer(Users $buyer): static
    {
        $this->buyer = $buyer;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStripeToken(): ?string
    {
        return $this->stripeToken;
    }

    public function setStripeToken(#[ \SensitiveParameter] ?string $stripeToken): static
    {
        $this->stripeToken = $stripeToken;
        return $this;
    }

}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Phone
{
    #[ORM\Column(name: 'phone', type: "string", length: 20, nullable: true)]
    private ?string $number = null;

    public function __construct(?string $number = null)
    {
        $this->number = $number;
    }

    public function getNumber(): ?string { return $this->number; }
    public function __toString(): string { return $this->number ?? ''; }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(name: 'email', type: "string", length: 255)]
    #[Assert\Email]
    private string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    public function getValue(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}

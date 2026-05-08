<?php

namespace App\Entity;

use App\Repository\FaceDataRepository;
use App\Entity\Email;
use Doctrine\ORM\Mapping as ORM;

use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: FaceDataRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FaceData
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    /** @var array<int|string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $face_descriptor;



    public function __construct()
    {
        $this->email = new Email();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        $this->email = $email;

        return $this;
    }

    /** @return array<int|string, mixed> */
    public function getFaceDescriptor(): array
    {
        return $this->face_descriptor;
    }

    /** @param array<int|string, mixed> $face_descriptor */
    public function setFaceDescriptor(array $face_descriptor): static
    {
        $this->face_descriptor = $face_descriptor;

        return $this;
    }

}

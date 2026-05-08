<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Email;

use App\Traits\TimestampableTrait;
use App\Traits\BlameableTrait;

#[ORM\Entity]
#[ORM\Table(name: 'reservation_maquillage')]
#[ORM\HasLifecycleCallbacks]
class ReservationMaquillage
{
    use TimestampableTrait;
    use BlameableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;



    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'reservationMaquillages')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_event', nullable: false, onDelete: 'CASCADE')]
    private Evenement $event;

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



    public function getEvent(): Evenement
    {
        return $this->event;
    }

    public function setEvent(Evenement $event): static
    {
        $this->event = $event;
        return $this;
    }


}
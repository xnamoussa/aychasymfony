<?php

namespace App\Traits;

use App\Entity\Users;
use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'updated_by_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $updatedBy = null;

    public function getCreatedBy(): ?Users { return $this->createdBy; }
    public function setCreatedBy(?Users $createdBy): void { $this->createdBy = $createdBy; }
    public function getUpdatedBy(): ?Users { return $this->updatedBy; }
    public function setUpdatedBy(?Users $updatedBy): void { $this->updatedBy = $updatedBy; }
}

<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le commentaire ne peut pas être vide.")]
    #[Assert\Length(
        min: 2,
        max: 1000,
        minMessage: "Le commentaire doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\p{P}]+$/u",
        message: "Le commentaire contient des caractères non autorisés."
    )]
    private string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Users $author;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getContent(): string { return $this->content; }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    
    protected function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPost(): Post { return $this->post; }

    public function setPost(Post $post): static
    {
        $this->post = $post;
        return $this;
    }

    public function getAuthor(): Users
    {
        return $this->author;
    }

    protected function setAuthor(Users $author): static
    {
        $this->author = $author;
        return $this;
    }
}

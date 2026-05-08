<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\p{P}]+$/u",
        message: "Le titre contient des caractères non autorisés."
    )]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le contenu ne peut pas dépasser {{ limit }} caractères."
    )]
    private string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    /** @var array<string, int> */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $reactions = [];

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Users $author;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->reactions = [
            '👍' => 0,
            '❤️' => 0,
            '😂' => 0,
            '😮' => 0,
            '😢' => 0,
            '😡' => 0
        ];
        $this->createdAt = new \DateTimeImmutable();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    protected function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return array<string, int> */
    public function getReactions(): array
    {
        if (empty($this->reactions)) {
            $this->reactions = [
                '👍' => 0,
                '❤️' => 0,
                '😂' => 0,
                '😮' => 0,
                '😢' => 0,
                '😡' => 0
            ];
        }
        return $this->reactions;
    }

    /** @param array<string, int>|null $reactions */
    public function setReactions(?array $reactions): static
    {
        if ($reactions === null) {
            $reactions = [
                '👍' => 0,
                '❤️' => 0,
                '😂' => 0,
                '😮' => 0,
                '😢' => 0,
                '😡' => 0
            ];
        }
        $this->reactions = $reactions;
        return $this;
    }

    public function addReaction(string $emoji): static
    {
        $reactions = $this->getReactions();
        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = 0;
        }
        $reactions[$emoji]++;
        $this->reactions = $reactions;
        return $this;
    }

    public function removeReaction(string $emoji): static
    {
        $reactions = $this->getReactions();
        if (isset($reactions[$emoji]) && $reactions[$emoji] > 0) {
            $reactions[$emoji]--;
        }
        $this->reactions = $reactions;
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        $this->comments->removeElement($comment);
        return $this;
    }
}

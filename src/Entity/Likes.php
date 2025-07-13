<?php

namespace App\Entity;

use App\Repository\LikesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikesRepository::class)]
class Likes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'likedItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $whoLikes = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $likedItem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWhoLikes(): ?User
    {
        return $this->whoLikes;
    }

    public function setWhoLikes(?User $whoLikes): static
    {
        $this->whoLikes = $whoLikes;

        return $this;
    }

    public function getLikedItem(): ?Item
    {
        return $this->likedItem;
    }

    public function setLikedItem(?Item $likedItem): static
    {
        $this->likedItem = $likedItem;

        return $this;
    }
}

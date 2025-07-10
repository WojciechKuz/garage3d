<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $photoname = null;

    #[ORM\Column(length: 255)]
    private ?string $serverPhotoname = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhotoname(): ?string
    {
        return $this->photoname;
    }

    public function setPhotoname(string $photoname): static
    {
        $this->photoname = $photoname;

        return $this;
    }

    public function getServerPhotoname(): ?string
    {
        return $this->serverPhotoname;
    }

    public function setServerPhotoname(string $serverPhotoname): static
    {
        $this->serverPhotoname = $serverPhotoname;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }
}

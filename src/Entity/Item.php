<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $iid = null;

    #[ORM\Column(length: 255)]
    private ?string $itemName = null;

    #[ORM\Column(length: 4096)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setIid(int $iid): static
    {
        $this->iid = $iid;

        return $this;
    }

    public function getItemName(): ?string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): static
    {
        $this->itemName = $itemName;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}

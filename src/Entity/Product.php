<?php


namespace App\Entity;


use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 64, unique: true)]
    private string $sku;


    #[ORM\Column(length: 255)]
    private string $name;


    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;


    #[ORM\Column(type: 'json')]
    private array $weatherTags = [];


    public function getId(): ?int { return $this->id; }


    public function getSku(): string { return $this->sku; }
    public function setSku(string $sku): self { $this->sku = $sku; return $this; }


    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }


    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }


    public function getWeatherTags(): array { return $this->weatherTags; }
    public function setWeatherTags(array $tags): self { $this->weatherTags = array_values($tags); return $this; }
}

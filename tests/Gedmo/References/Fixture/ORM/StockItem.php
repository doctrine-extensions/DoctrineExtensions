<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\References\Fixture\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\References\Fixture\ODM\MongoDB\Product;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class StockItem
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column
     */
    #[ORM\Column]
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column
     */
    #[ORM\Column]
    private $sku;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $quantity;

    /**
     * @var Product|null
     *
     * @Gedmo\ReferenceOne(type="document", class="Gedmo\Tests\References\Fixture\ODM\MongoDB\Product", inversedBy="stockItems", identifier="productId")
     */
    #[Gedmo\ReferenceOne(type: 'document', class: Product::class, inversedBy: 'stockItems', identifier: 'productId')]
    private $product;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private $productId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }
}

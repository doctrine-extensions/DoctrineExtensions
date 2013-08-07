<?php

namespace Gedmo\Fixture\References\ORM;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Fixture\References\ODM\MongoDB\Product;

/**
 * @ORM\Entity
 */
class StockItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $name;

    /**
     * @ORM\Column
     */
    private $sku;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @Gedmo\ReferenceOne(type="document", class="Gedmo\Fixture\References\ODM\MongoDB\Product", inversedBy="stockItems", identifier="productId")
     */
    private $product;

    /**
     * @ORM\Column(type="string")
     */
    private $productId;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    public function getProductId()
    {
        return $this->productId;
    }
}

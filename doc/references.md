# Cross Object Mapper References behavior extension for Doctrine

Create documents and entities that contain references to each other.

## Options

The following options are possible on reference one and many associations:

**Owning Side**

- **type** - The type of association.
- **class** - The associated class name.
- **inversedBy** - The property name for the inverse side of this association.
- **identifier** - The property name to store the associated object id in.

**Inverse Side**

- **type** - The type of association.
- **class** - The associated class name.
- **mappedBy** - The property name for the owning side of this association.

## Annotations and attributes

**Gedmo\ReferenceMany**

```php
<?php

/**
 * @Gedmo\ReferenceMany(type="entity", class="Entity\StockItem", mappedBy="product")
 */
#[Gedmo\ReferenceMany(type: 'entity', class: Entity\StockItem::class, mappedBy: 'product')] 
private $stockItems;
```

**Gedmo\ReferenceOne**

```php
<?php

/**
 * @Gedmo\ReferenceOne(type="document", class="Document\Product", inversedBy="stockItems", identifier="productId")
 */
#[Gedmo\ReferenceOne(type: 'document', class: Document\Product::class, inversedBy: 'stockItems', identifier: 'productId')]
private $product;
```

## Example

Here is an example where you have a Product which is mapped using the Doctrine MongoDB ODM project and it contains a property `$stockItems` that is populated from the Doctrine ORM.

```php
<?php

namespace Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 */
#[ODM\Document]
class Product
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $name;

    /**
     * @Gedmo\ReferenceMany(type="entity", class="Entity\StockItem", mappedBy="product")
     */
    #[Gedmo\ReferenceMany(type: 'entity', class: Entity\StockItem::class, mappedBy: 'product')]
    private $stockItems;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getStockItems()
    {
        return $this->stockItems;
    }

    public function setStockItems(Collection $stockItems)
    {
        $this->stockItems = $stockItems;
    }
}
```

The `StockItem` has a reference to the `Product` as well.

```php
<?php

namespace Entity;

use Doctrine\DBAL\Types\Types;use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use References\Fixture\ODM\MongoDB\Product;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class StockItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @ORM\Column
     */
    #[ORM\Column]
    private $name;

    /**
     * @ORM\Column
     */
    #[ORM\Column]
    private $sku;

    /**
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $quantity;

    /**
     * @Gedmo\ReferenceOne(type="document", class="Document\Product", inversedBy="stockItems", identifier="productId")
     */
    #[Gedmo\ReferenceOne(type: 'document', class: Document\Product::class, inversedBy: 'stockItems', identifier: 'productId')]
    private $product;

    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private $productId;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
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
```

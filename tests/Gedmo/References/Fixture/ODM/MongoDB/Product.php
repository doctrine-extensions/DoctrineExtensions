<?php

namespace References\Fixture\ODM\MongoDB;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 */
class Product
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\String
     */
    private $name;

    /**
     * @Gedmo\ReferenceMany(type="entity", class="References\Fixture\ORM\StockItem", mappedBy="product")
     */
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

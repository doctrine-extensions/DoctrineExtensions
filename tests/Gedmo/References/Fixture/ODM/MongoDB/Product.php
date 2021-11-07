<?php

namespace Gedmo\Tests\References\Fixture\ODM\MongoDB;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @Gedmo\ReferenceMany(type="entity", class="Gedmo\Tests\References\Fixture\ORM\StockItem", mappedBy="product")
     */
    private $stockItems;

    /**
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\References\Fixture\ODM\MongoDB\Metadata")
     */
    private $metadatas;

    public function __construct()
    {
        $this->metadatas = new ArrayCollection();
    }

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

    public function addMetadata($metadata)
    {
        $this->metadatas[] = $metadata;
    }

    public function removeMetadata($metadata)
    {
        $this->metadatas->removeElement($metadata);
    }

    public function getMetadatas()
    {
        return $this->metadatas;
    }
}

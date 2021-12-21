<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var Collection<int, Metadata>
     *
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

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getStockItems()
    {
        return $this->stockItems;
    }

    public function setStockItems(Collection $stockItems): void
    {
        $this->stockItems = $stockItems;
    }

    public function addMetadata($metadata): void
    {
        $this->metadatas[] = $metadata;
    }

    public function removeMetadata($metadata): void
    {
        $this->metadatas->removeElement($metadata);
    }

    public function getMetadatas(): Collection
    {
        return $this->metadatas;
    }
}

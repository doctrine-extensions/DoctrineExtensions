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
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\References\Fixture\ORM\StockItem;

/**
 * @ODM\Document
 */
#[ODM\Document]
class Product
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $name;

    /**
     * @var Collection<int, StockItem>
     *
     * @Gedmo\ReferenceMany(type="entity", class="Gedmo\Tests\References\Fixture\ORM\StockItem", mappedBy="product")
     */
    #[Gedmo\ReferenceMany(type: 'entity', class: StockItem::class, mappedBy: 'product')]
    private $stockItems;

    /**
     * @var Collection<int, Metadata>
     *
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\References\Fixture\ODM\MongoDB\Metadata")
     */
    #[ODM\EmbedMany(targetDocument: Metadata::class)]
    private $metadatas;

    public function __construct()
    {
        $this->metadatas = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, StockItem>
     */
    public function getStockItems(): Collection
    {
        return $this->stockItems;
    }

    /**
     * @param Collection<int, StockItem> $stockItems
     */
    public function setStockItems(Collection $stockItems): void
    {
        $this->stockItems = $stockItems;
    }

    public function addMetadata(Metadata $metadata): void
    {
        $this->metadatas[] = $metadata;
    }

    public function removeMetadata(Metadata $metadata): void
    {
        $this->metadatas->removeElement($metadata);
    }

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadatas(): Collection
    {
        return $this->metadatas;
    }
}

<?php

namespace Fixture\References\ORM;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Fixture\References\ODM\MongoDB\Product;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $name;

    /**
     * @Gedmo\ReferenceManyEmbed(class="Fixture\References\ODM\MongoDB\Product", identifier="metadatas.categoryId")
     */
    private $products;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getProducts()
    {
        return $this->products;
    }
}

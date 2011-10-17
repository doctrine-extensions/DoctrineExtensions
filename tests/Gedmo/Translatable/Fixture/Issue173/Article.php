<?php

namespace Translatable\Fixture\Issue173;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;
    
    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     */
    private $category;

    
    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
    public function setCategory(Category $category)
    {
        $this->category = $category;
    }
    
    public function getCategory()
    {
        return $this->category;
    }
}


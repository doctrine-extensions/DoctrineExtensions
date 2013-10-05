<?php

namespace Sluggable\Fixture\Issue827;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Sluggable\Fixture\Issue827\Category;

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
     * @ORM\Column(name="title", length=64)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     */
    private $category;

    /**
     * @Gedmo\Slug(updatable=true, unique=true, unique_base="category", fields={"title"})
     * @ORM\Column(length=64, nullable=true)
     */
    private $slug;
    
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

    public function getSlug()
    {
        return $this->slug;
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

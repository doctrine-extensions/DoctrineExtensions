<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="category_translations", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="lookup_unique_idx", columns={"locale", "object_id"}),
 *   @ORM\UniqueConstraint(name="slug_unique_idx", columns={"slug"})
 * })
 */
class CategoryTranslation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $object;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;

    /**
     * Convinient constructor
     *
     * @param string $locale
     * @param string $title
     * @param string $description
     */
    public function __construct($locale = null, $title = null, $description = null)
    {
        $this->locale = $locale;
        $this->title = $title;
        $this->description = $description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}

<?php

namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(name="bc_translation_unique_idx", columns={"locale", "object_id"})
 * })
 */
class BehavioralCategoryTranslation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="BehavioralCategory", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $object;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;

    public function __construct($locale = null, $title = null)
    {
        $this->locale = $locale;
        $this->title = $title;
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

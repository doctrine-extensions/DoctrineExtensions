<?php

namespace Gedmo\Fixture\Translatable\Sluggable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(name="post_translation_unique_idx", columns={"locale", "object_id"})
 * })
 */
class PostTranslation extends AbstractTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="translations")
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
    private $content;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;

    public function __construct($locale = null, $title = null, $content = null)
    {
        $this->locale = $locale;
        $this->title = $title;
        $this->content = $content;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
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

<?php

namespace Sluggable\Fixture\Issue1151;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Sluggable\Fixture\Issue1151\Article
 *
 * @ODM\Document
 */
class Article
{
    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $id;

    /**
     * @ODM\String
     */
    protected $title;

    /**
     * @Gedmo\Slug(separator="-", updatable=true, fields={"title"})
     * @ODM\String
     */
    protected $slug;

    /**
     * Setter of Id
     *
     * @param string $id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Getter of Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter of Slug
     *
     * @param string $slug
     *
     * @return static
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Getter of Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Setter of Title
     *
     * @param string $title
     *
     * @return static
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Getter of Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}

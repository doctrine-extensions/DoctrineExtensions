<?php

namespace Sluggable\Fixture\Document\Handler;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 */
class TreeSlug
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @Gedmo\Slug(handlers={
     *      @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
     *          @Gedmo\SlugHandlerOption(name="parentRelationField", value="parent"),
     *          @Gedmo\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, separator="-", updatable=true, fields={"title"})
     * @ODM\String
     */
    private $alias;

    /**
     * @ODM\ReferenceOne(targetDocument="TreeSlug")
     */
    private $parent;

    public function setParent(TreeSlug $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

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
        return $this->alias;
    }
}
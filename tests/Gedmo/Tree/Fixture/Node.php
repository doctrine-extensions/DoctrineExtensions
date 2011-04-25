<?php

namespace Tree\Fixture;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Node extends BaseNode
{
    /**
     * @gedmo:Translatable
     * @gedmo:Sluggable
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @gedmo:Translatable
     * @gedmo:Slug
     * @Column(name="slug", type="string", length=128)
     */
    private $slug;

    public function getSlug()
    {
        return $this->slug;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
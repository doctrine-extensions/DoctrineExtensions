<?php

namespace Gedmo\Tests\Timestampable\Fixture\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="books")
 */
class Book
{
    /**
     * @ODM\Id()
     *
     * @var string
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $title;

    /**
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\Timestampable\Fixture\Document\Tag")
     *
     * @var Tag[]|Collection
     */
    protected $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return Tag[]|Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(Collection $tags)
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
    }
}

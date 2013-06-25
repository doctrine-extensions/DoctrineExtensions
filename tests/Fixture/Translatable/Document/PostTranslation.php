<?php

namespace Fixture\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation;

/**
 * @MongoODM\Document(collection="post_translations")
 */
class PostTranslation extends AbstractTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="Post", inversedBy="translations")
     */
    protected $object;

    /**
     * @MongoODM\String
     */
    private $title;

    public function __construct($locale = null, $title = null)
    {
        $this->locale = $locale;
        $this->title = $title;
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

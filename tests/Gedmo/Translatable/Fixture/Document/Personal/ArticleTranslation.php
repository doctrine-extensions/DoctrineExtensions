<?php

namespace Gedmo\Tests\Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @MongoODM\Document(collection="article_translations")
 */
class ArticleTranslation extends AbstractPersonalTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="Gedmo\Tests\Translatable\Fixture\Document\Personal\Article", inversedBy="translations")
     */
    protected $object;
}

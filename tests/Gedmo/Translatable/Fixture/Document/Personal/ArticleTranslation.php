<?php
namespace Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @MongoODM\Document(collection="article_translations")
 */
class ArticleTranslation extends AbstractPersonalTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="Translatable\Fixture\Document\Personal\Article", inversedBy="translations")
     */
    protected $object;
}

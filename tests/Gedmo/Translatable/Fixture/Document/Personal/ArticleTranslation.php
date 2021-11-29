<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @MongoODM\Document(collection="article_translations")
 */
#[MongoODM\Document(collection: 'article_translations')]
class ArticleTranslation extends AbstractPersonalTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="Gedmo\Tests\Translatable\Fixture\Document\Personal\Article", inversedBy="translations")
     */
    #[MongoODM\ReferenceOne(targetDocument: Article::class, inversedBy: 'translations')]
    protected $object;
}

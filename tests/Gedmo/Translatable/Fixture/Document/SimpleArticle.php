<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoODM\Document(collection="articles")
 */
class SimpleArticle
{
    /** @MongoODM\Id */
    private $id;

    /**
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $content;

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

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
}

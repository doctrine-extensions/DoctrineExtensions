<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Wrapper\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * @MongoODM\Document(collection="articles")
 */
class Article
{
    /** @MongoODM\Id */
    private $id;

    /**
     * @MongoODM\Field(type="string")
     */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}

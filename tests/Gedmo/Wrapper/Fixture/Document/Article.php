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
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @MongoODM\Document(collection="articles")
 */
#[MongoODM\Document(collection: 'article')]
class Article
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @MongoODM\Field(type="string")
     */
    #[MongoODM\Field(type: Type::STRING)]
    private $title;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}

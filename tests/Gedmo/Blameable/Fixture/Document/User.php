<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;

/**
 * @ODM\Document(collection="users")
 */
#[ODM\Document(collection: 'users')]
class User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private $username;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }
}

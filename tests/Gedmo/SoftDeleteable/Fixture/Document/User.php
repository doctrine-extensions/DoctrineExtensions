<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="users")
 *
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
#[ODM\Document(collection: 'users')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class User
{
    /**
     * @var \DateTimeInterface|null
     *
     * @ODM\Field(type="date")
     */
    #[ODM\Field(type: Type::DATE)]
    protected $deletedAt;
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private ?string $username = null;

    public function setDeletedAt(\DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

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

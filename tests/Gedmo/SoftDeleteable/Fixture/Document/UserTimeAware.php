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

#[ODM\Document(collection: 'users')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class UserTimeAware
{
    #[ODM\Field(type: Type::DATE)]
    protected ?\DateTimeInterface $deletedAt = null;
    /**
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: Type::STRING)]
    private ?string $username = null;

    public function setDeletedAt(\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
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

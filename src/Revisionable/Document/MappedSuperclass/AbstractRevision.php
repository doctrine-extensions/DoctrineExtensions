<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionInterface;

/**
 * Base class defining a revision with all mapping configuration for the Doctrine MongoDB ODM.
 *
 * @template T of Revisionable|object
 *
 * @template-implements RevisionInterface<T>
 *
 * @ODM\MappedSuperclass
 */
#[ODM\MappedSuperclass]
abstract class AbstractRevision implements RevisionInterface
{
    /**
     * @ODM\Id(name="id")
     */
    #[ODM\Id(name: 'id')]
    protected ?string $id = null;

    /**
     * @var self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE
     *
     * @ODM\Field(name="action", type="string")
     */
    #[ODM\Field(name: 'action', type: Type::STRING)]
    protected string $action = self::ACTION_CREATE;

    /**
     * @var positive-int
     *
     * @ODM\Field(name="version", type="int")
     */
    #[ODM\Field(name: 'version', type: Type::INT)]
    protected int $version = 1;

    /**
     * @var non-empty-string|null
     *
     * @ODM\Field(name="revisionable_id", type="string", nullable=true)
     */
    #[ODM\Field(name: 'revisionable_id', type: Type::STRING, nullable: true)]
    protected ?string $revisionableId = null;

    /**
     * @var class-string<T>|null
     *
     * @ODM\Field(name="revisionable_class", type="string")
     */
    #[ODM\Field(name: 'revisionable_class', type: Type::STRING)]
    protected ?string $revisionableClass = null;

    /**
     * @ODM\Field(name="logged_at", type="date_immutable")
     */
    #[ODM\Field(name: 'logged_at', type: Type::DATE_IMMUTABLE)]
    protected \DateTimeImmutable $loggedAt;

    /**
     * @var non-empty-string|null
     *
     * @ODM\Field(name="username", type="string", nullable=true)
     */
    #[ODM\Field(name: 'username', type: Type::STRING, nullable: true)]
    protected ?string $username = null;

    /**
     * @var array<string, mixed>
     *
     * @ODM\Field(name="data", type="hash")
     */
    #[ODM\Field(name: 'data', type: Type::HASH)]
    protected array $data = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param positive-int $version
     */
    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * @return positive-int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param non-empty-string $revisionableId
     */
    public function setRevisionableId(string $revisionableId): void
    {
        $this->revisionableId = $revisionableId;
    }

    /**
     * @return non-empty-string|null
     */
    public function getRevisionableId(): ?string
    {
        return $this->revisionableId;
    }

    /**
     * @param class-string<T> $revisionableClass
     */
    public function setRevisionableClass(string $revisionableClass): void
    {
        $this->revisionableClass = $revisionableClass;
    }

    /**
     * @return class-string<T>|null
     */
    public function getRevisionableClass(): ?string
    {
        return $this->revisionableClass;
    }

    public function setLoggedAt(\DateTimeImmutable $loggedAt): void
    {
        $this->loggedAt = $loggedAt;
    }

    public function getLoggedAt(): \DateTimeImmutable
    {
        return $this->loggedAt;
    }

    /**
     * @param non-empty-string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return non-empty-string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}

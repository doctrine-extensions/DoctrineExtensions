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
 * @phpstan-template T of Revisionable|object
 *
 * @phpstan-implements RevisionInterface<T>
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
     * @phpstan-var self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE|null
     *
     * @ODM\Field(name="action", type="string")
     */
    #[ODM\Field(name: 'action', type: Type::STRING)]
    protected ?string $action = null;

    /**
     * @phpstan-var positive-int
     *
     * @ODM\Field(name="version", type="int")
     */
    #[ODM\Field(name: 'version', type: Type::INT)]
    protected int $version = 1;

    /**
     * @phpstan-var non-empty-string|null
     *
     * @ODM\Field(name="revisionable_id", type="string", nullable=true)
     */
    #[ODM\Field(name: 'revisionable_id', type: Type::STRING, nullable: true)]
    protected ?string $revisionableId = null;

    /**
     * @phpstan-var class-string<T>|null
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
     * @phpstan-var non-empty-string|null
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
     * @phpstan-param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @phpstan-return self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @phpstan-param positive-int $version
     */
    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * @phpstan-return positive-int
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
     * @phpstan-param class-string<T> $revisionableClass
     */
    public function setRevisionableClass(string $revisionableClass): void
    {
        $this->revisionableClass = $revisionableClass;
    }

    /**
     * @phpstan-return class-string<T>|null
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
     * @phpstan-param non-empty-string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @phpstan-return non-empty-string|null
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

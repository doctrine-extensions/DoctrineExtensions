<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Entity\MappedSuperclass;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionInterface;

/**
 * Base class defining a revision with all mapping configuration for the Doctrine ORM.
 *
 * @phpstan-template T of Revisionable|object
 *
 * @phpstan-implements RevisionInterface<T>
 *
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
abstract class AbstractRevision implements RevisionInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @phpstan-var self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE|null
     *
     * @ORM\Column(name="action", type="string", length=8)
     */
    #[ORM\Column(name: 'action', type: Types::STRING, length: 8)]
    protected ?string $action = null;

    /**
     * @phpstan-var positive-int
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(name: 'version', type: Types::INTEGER)]
    protected int $version = 1;

    /**
     * @phpstan-var non-empty-string|null
     *
     * @ORM\Column(name="revisionable_id", type="string", length=64, nullable=true)
     */
    #[ORM\Column(name: 'revisionable_id', type: Types::STRING, length: 64, nullable: true)]
    protected ?string $revisionableId = null;

    /**
     * @phpstan-var class-string<T>|null
     *
     * @ORM\Column(name="revisionable_class", type="string", length=191)
     */
    #[ORM\Column(name: 'revisionable_class', type: Types::STRING, length: 191)]
    protected ?string $revisionableClass = null;

    /**
     * @ORM\Column(name="logged_at", type="datetime_immutable")
     */
    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $loggedAt;

    /**
     * @phpstan-var non-empty-string|null
     *
     * @ORM\Column(name="username", length=191, nullable=true)
     */
    #[ORM\Column(name: 'username', length: 191, nullable: true)]
    protected ?string $username = null;

    /**
     * @var array<string, mixed>
     *
     * @ORM\Column(name="data", type="json")
     */
    #[ORM\Column(name: 'data', type: Types::JSON)]
    protected array $data = [];

    public function getId(): ?int
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

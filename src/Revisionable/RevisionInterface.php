<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable;

/**
 * Interface defining a revision model.
 *
 * @template T of Revisionable|object
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
interface RevisionInterface
{
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_REMOVE = 'remove';

    /**
     * Named constructor to create a new revision.
     *
     * Implementations should handle setting the initial logged at time and version for new instances within this constructor.
     *
     * @param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     *
     * @return RevisionInterface<T>
     */
    public static function createRevision(string $action): self;

    /**
     * @param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     */
    public function setAction(string $action): void;

    /**
     * @return self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE
     */
    public function getAction(): string;

    /**
     * @param positive-int $version
     */
    public function setVersion(int $version): void;

    /**
     * @return positive-int
     */
    public function getVersion(): int;

    /**
     * @param non-empty-string $revisionableId
     */
    public function setRevisionableId(string $revisionableId): void;

    /**
     * @return non-empty-string|null
     */
    public function getRevisionableId(): ?string;

    /**
     * @param class-string<T> $revisionableClass
     */
    public function setRevisionableClass(string $revisionableClass): void;

    /**
     * @return class-string<T>|null
     */
    public function getRevisionableClass(): ?string;

    public function setLoggedAt(\DateTimeImmutable $loggedAt): void;

    public function getLoggedAt(): \DateTimeImmutable;

    /**
     * @param non-empty-string|null $username
     */
    public function setUsername(?string $username): void;

    /**
     * @return non-empty-string|null
     */
    public function getUsername(): ?string;

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
}

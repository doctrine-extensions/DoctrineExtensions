<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

/**
 * Interface to be implemented by log entry models.
 *
 * @phpstan-template T of Loggable|object
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
interface LogEntryInterface
{
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_REMOVE = 'remove';

    /**
     * @phpstan-param self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE $action
     *
     * @return void
     */
    public function setAction(string $action);

    /**
     * @return string|null
     *
     * @phpstan-return self::ACTION_CREATE|self::ACTION_UPDATE|self::ACTION_REMOVE|null
     */
    public function getAction();

    /**
     * @return void
     */
    public function setUsername(string $username);

    /**
     * @return string|null
     */
    public function getUsername();

    /**
     * @phpstan-param class-string<T> $objectClass
     *
     * @return void
     */
    public function setObjectClass(string $objectClass);

    /**
     * @return string|null
     *
     * @phpstan-return class-string<T>
     */
    public function getObjectClass();

    /**
     * @return void
     */
    public function setLoggedAt();

    /**
     * @return \DateTimeInterface|null
     */
    public function getLoggedAt();

    /**
     * @return void
     */
    public function setObjectId(string $objectId);

    /**
     * @return string|null
     */
    public function getObjectId();

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function setData(array $data);

    /**
     * @return array<string, mixed>|null
     */
    public function getData();

    /**
     * @return void
     */
    public function setVersion(int $version);

    /**
     * @return int|null
     */
    public function getVersion();
}

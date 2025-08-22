<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as OdmClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Exception\InvalidMappingException;

/**
 * The mapping driver interface defines the metadata extraction functions
 * common among all drivers used on these extensions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Driver
{
    /**
     * Read the extended metadata configuration for a single mapped class.
     *
     * @todo In the next major release stop receiving by reference the `$config` parameter and use `array` as return type declaration
     *
     * @param ClassMetadata<T>     $meta
     * @param array<string, mixed> $config
     *
     * @throws InvalidMappingException if the mapping configuration is invalid
     *
     * @return void
     *
     * @template T of object
     *
     * @phpstan-param ClassMetadata<T>&(OdmClassMetadata<T>|OrmClassMetadata<T>) $meta
     */
    public function readExtendedMetadata($meta, array &$config);

    /**
     * Sets the original mapping driver.
     *
     * @param MappingDriver $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver);
}

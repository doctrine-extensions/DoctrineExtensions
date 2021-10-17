<?php

namespace Gedmo\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Exception\InvalidMappingException;

/**
 * The mapping driver interface defines the metadata extraction functions
 * common among all drivers used on these extensions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Driver
{
    /**
     * Read the extended metadata configuration for a single mapped class.
     *
     * @param ClassMetadata $meta
     *
     * @return void
     *
     * @throws InvalidMappingException if the mapping configuration is invalid
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

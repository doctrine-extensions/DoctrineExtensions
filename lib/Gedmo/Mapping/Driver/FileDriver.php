<?php

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Mapping\ExtensionDriverInterface;

/**
 * The mapping FileDriver abstract class, defines the
 * metadata extraction function common among
 * all drivers used on these extensions by file based
 * drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class FileDriver implements ExtensionDriverInterface
{
    /**
     * original driver
     */
    protected $originalDriver;

    /**
     * Tries to get a mapping for a given class
     *
     * @param string $className
     *
     * @return null|array|object
     */
    protected function getMapping($className)
    {
        return $this->originalDriver->getElement($className);
    }

    /**
     * {@inheritDoc}
     */
    public function setOriginalDriver(MappingDriver $driver)
    {
        $this->originalDriver = $driver;
    }
}

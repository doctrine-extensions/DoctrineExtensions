<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\ExtensionDriverInterface;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

/**
 * The chain mapping driver enables chained
 * extension mapping driver support
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class DriverChain implements ExtensionDriverInterface
{
    /**
     * List of drivers nested
     *
     * @var array
     */
    private $drivers = array();

    /**
     * Add a nested driver.
     *
     * @param \Gedmo\Mapping\ExtensionDriverInterface $driver
     * @param string $namespace
     */
    public function addDriver(ExtensionDriverInterface $driver, $namespace)
    {
        $this->drivers[$namespace] = $driver;
    }

    /**
     * Get the array of nested drivers.
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Set the default driver, as original through interface.
     *
     * {@inheritDoc}
     */
    public function setOriginalDriver(MappingDriver $driver)
    {
        // nothing here, default driver of chain will be added into chain as last suitable
    }

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        foreach ($this->drivers as $namespace => $driver) {
            if (strpos($meta->name, $namespace) === 0) {
                $driver->loadExtensionMetadata($meta, $exm);
                return;
            }
        }
    }
}

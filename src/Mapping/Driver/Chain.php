<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Driver;

/**
 * The chain mapping driver enables chained
 * extension mapping driver support
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Chain implements Driver
{
    /**
     * The default driver
     *
     * @var Driver|null
     */
    private $defaultDriver;

    /**
     * List of drivers nested
     *
     * @var Driver[]
     */
    private $_drivers = [];

    /**
     * Add a nested driver.
     *
     * @param string $namespace
     */
    public function addDriver(Driver $nestedDriver, $namespace)
    {
        $this->_drivers[$namespace] = $nestedDriver;
    }

    /**
     * Get the array of nested drivers.
     *
     * @return Driver[] $drivers
     */
    public function getDrivers()
    {
        return $this->_drivers;
    }

    /**
     * Get the default driver.
     *
     * @return Driver|null
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver.
     */
    public function setDefaultDriver(Driver $driver)
    {
        $this->defaultDriver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        foreach ($this->_drivers as $namespace => $driver) {
            if (0 === strpos($meta->name, $namespace)) {
                $driver->readExtendedMetadata($meta, $config);

                return;
            }
        }

        if (null !== $this->defaultDriver) {
            $this->defaultDriver->readExtendedMetadata($meta, $config);

            return;
        }

        // commenting it for customized mapping support, debugging of such cases might get harder
        //throw new \Gedmo\Exception\UnexpectedValueException('Class ' . $meta->name . ' is not a valid entity or mapped super class.');
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        //not needed here
    }
}

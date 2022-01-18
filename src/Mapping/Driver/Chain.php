<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Driver;

/**
 * The chain mapping driver enables chained
 * extension mapping driver support
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     *
     * @return void
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
     *
     * @return void
     */
    public function setDefaultDriver(Driver $driver)
    {
        $this->defaultDriver = $driver;
    }

    public function readExtendedMetadata($meta, array &$config)
    {
        foreach ($this->_drivers as $namespace => $driver) {
            if (0 === strpos($meta->getName(), $namespace)) {
                $driver->readExtendedMetadata($meta, $config);

                return;
            }
        }

        if (null !== $this->defaultDriver) {
            $this->defaultDriver->readExtendedMetadata($meta, $config);

            return;
        }

        // commenting it for customized mapping support, debugging of such cases might get harder
        //throw new \Gedmo\Exception\UnexpectedValueException('Class ' . $meta->getName() . ' is not a valid entity or mapped super class.');
    }

    /**
     * Passes in the mapping read by original driver
     */
    public function setOriginalDriver($driver)
    {
        //not needed here
    }
}

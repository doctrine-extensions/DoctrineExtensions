<?php

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Annotations\Reader;
use Gedmo\Mapping\ExtensionDriverInterface;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Derek J. Lambert <dlambert@dereklambert.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AnnotationDriver implements ExtensionDriverInterface
{
    /**
     * The AnnotationReader.
     *
     * @var Reader
     */
    protected $reader;

    /**
     * @var MappingDriver
     */
    protected $originalDriver;

    /**
     * {@inheritDoc}
     */
    public function setOriginalDriver(MappingDriver $driver)
    {
        $this->originalDriver = $driver;
    }

    /**
     * Set annotation reader
     *
     * @param Reader $reader
     */
    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Retrieve the current annotation reader
     *
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }
}

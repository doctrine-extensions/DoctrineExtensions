<?php

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\Driver\AbstractFileDriver;
use Gedmo\Mapping\Driver;

/**
 * The mapping FileDriver abstract class, defines the
 * metadata extraction function common among
 * all drivers used on these extensions by file based
 * drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class File implements Driver
{
    /**
     * @var FileLocator
     */
    protected $locator;

    /**
     * File extension, must be set in child class
     * @var string
     */
    protected $_extension;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    public function setLocator(FileLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Set the paths for file lookup
     *
     * @param array $paths
     * @return void
     */
    public function setPaths($paths)
    {
        $this->_paths = (array)$paths;
    }

    /**
     * Set the file extension
     *
     * @param string $extension
     * @return void
     */
    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding elements.
     *
     * @param string $file The mapping file to load.
     * @return array
     */
    abstract protected function _loadMappingFile($file);

    /**
     * Tries to get a mapping for a given class
     *
     * @param  $className
     * @return null|array|object
     */
    protected function _getMapping($className)
    {
        //try loading mapping from original driver first
        $mapping = null;
        if (!is_null($this->_originalDriver)) {
            if ($this->_originalDriver instanceof FileDriver || $this->_originalDriver instanceof AbstractFileDriver) {
                $mapping = $this->_originalDriver->getElement($className);
            }
        }

        //if no mapping found try to load mapping file again
        if (is_null($mapping)) {
            $yaml = $this->_loadMappingFile($this->locator->findMappingFile($className));
            $mapping = $yaml[$className];
        }

        return $mapping;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}

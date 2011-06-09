<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Doctrine\ORM\Mapping\Driver\AbstractFileDriver as ORMAbstractFileDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AbstractFileDriver as MongoDBAbstractFileDriver;

/**
 * The mapping FileDriver abstract class, defines the
 * metadata extraction function common among
 * all drivers used on these extensions by file based
 * drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Common.Mapping
 * @subpackage FileDriver
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class File implements Driver
{
    /**
     * File extension, must be set in child class
     * @var string
     */
    protected $_extension;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * List of paths for file search
     * @var array
     */
    private $_paths = array();

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
     * Finds the mapping file for the class with the given name by searching
     * through the configured paths.
     *
     * @param $className
     * @return string The (absolute) file name.
     * @throws RuntimeException if not found
     */
    protected function _findMappingFile($className)
    {
        $fileName = str_replace('\\', '.', $className) . $this->_extension;

        // Check whether file exists
        foreach ((array) $this->_paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $fileName)) {
                return $path . DIRECTORY_SEPARATOR . $fileName;
            }
        }
        throw new \Gedmo\Exception\UnexpectedValueException("No mapping file found named '$fileName' for class '$className'.");
    }

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
            if ($this->_originalDriver instanceof ORMAbstractFileDriver || $this->_originalDriver instanceof MongoDBAbstractFileDriver) {
                $mapping = $this->_originalDriver->getElement($className);
            }
        }

        //if no mapping found try to load mapping file again
        if (is_null($mapping)) {
            $yaml = $this->_loadMappingFile($this->_findMappingFile($className));
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
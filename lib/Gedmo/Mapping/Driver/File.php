<?php

namespace Gedmo\Mapping\Driver;

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
abstract class File
{
    /**
     * File extension, must be set in child class
     * @var string
     */
    protected $_extension;
    
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

        throw \Gedmo\Mapping\DriverException::mappingFileNotFound($fileName, $className);
    }
}
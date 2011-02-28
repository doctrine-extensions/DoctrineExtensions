<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.Mapping.Driver
 * @subpackage Yaml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $actions = array(
        'create', 'update', 'delete'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config)
    {
        if (isset($config['actions']) && is_array($config['actions'])) {
            foreach ($config['actions'] as $action) {
                if (!in_array($this->actions, $action)) {
                    throw new InvalidMappingException("Action {$action} for class: {$meta->name} is invalid");
                }
            }
        }

        if (isset($config['actions']) && !is_array($config['actions'])) {
            throw new InvalidMappingException("Actions for class: {$meta->name} should be an array");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $yaml = $this->_loadMappingFile($this->_findMappingFile($meta->name));
        $mapping = $yaml[$meta->name];

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['loggable']['actions'])) {
                $actions = $classMapping['loggable']['actions'];
                $config['actions'] = $actions;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
}

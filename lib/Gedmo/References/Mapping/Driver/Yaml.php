<?php
/**
 * Yaml.php
 *
 * @author dbojdo - Daniel Bojdo <daniel.bojdo@dxi.eu>
 * Created on May 18, 2015, 14:20
 * Copyright (C) DXI Ltd
 */

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * Class Yaml
 * @package Gedmo\References\Mapping\Driver
 */
class Yaml extends File implements Driver
{
    private static $referenceTypes = array(
        'referenceOne',
        'referenceMany',
        'referenceManyEmbed'
    );

    /**
     * Read extended metadata configuration for
     * a single mapped class
     *
     * @param object $meta
     * @param array $config
     *
     * @return void
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);
        foreach (self::$referenceTypes as $referenceType) {
            if (isset($mapping['gedmo'][$referenceType])) {
                foreach ($mapping['gedmo'][$referenceType] as $field => $referenceConfig) {
                    $referenceConfig['field'] = $field;
                    $config[$referenceType][] = $referenceConfig;
                }
            }
        }
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding elements.
     *
     * @param string $file The mapping file to load.
     *
     * @return array
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}

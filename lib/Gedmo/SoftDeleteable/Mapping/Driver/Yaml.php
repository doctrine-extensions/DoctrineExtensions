<?php

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\SoftDeleteable\Mapping\Validator;

/**
 * This is a yaml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Timestampable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['soft_deleteable'])) {
                $config['softDeleteable'] = true;

                if (!isset($classMapping['soft_deleteable']['field_name'])) {
                    throw new InvalidMappingException('Field name for SoftDeleteable class is mandatory.');
                }

                $fieldName = $classMapping['soft_deleteable']['field_name'];

                Validator::validateField($meta, $fieldName);

                $config['fieldName'] = $fieldName;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}

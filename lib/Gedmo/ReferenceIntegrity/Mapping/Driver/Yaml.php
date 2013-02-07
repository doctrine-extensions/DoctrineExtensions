<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\File;
use Gedmo\Mapping\Driver;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * This is a yaml mapping driver for ReferenceIntegrity
 * extension. Used for extraction of extended
 * metadata from yaml specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @package Gedmo.ReferenceIntegrity.Mapping.Driver
 * @subpackage Yaml
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
        $validator = new Validator();

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $property => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['referenceIntegrity']['actions'])) {
                    foreach ($fieldMapping['gedmo']['referenceIntegrity']['actions'] as $field => $action) {
                        if (!$meta->hasField($property)) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Unable to find reference integrity [%s] as mapped property in entity - %s",
                                    $property,
                                    $meta->name
                                )
                            );
                        }

                        if (!in_array($action, $validator->getIntegrityActions())) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Field - [%s] does not have a valid integrity option, [%s] in class - %s",
                                    $property,
                                    implode($validator->getIntegrityActions(), ', '),
                                    $meta->name
                                )
                            );
                        }

                        $config['referenceIntegrities'][$property][$field] = $action;
                    }
                }
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

<?php

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Mapping\Driver\File;
use Gedmo\Mapping\Driver;
use Gedmo\Exception\InvalidMappingException;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';

    private $validReferences = array(
        'referenceOne'       => array(),
        'referenceMany'      => array(),
        'referenceManyEmbed' => array(),
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo']) && isset($mapping['gedmo']['reference'])) {

            foreach ($mapping['gedmo']['reference'] as $field => $fieldMapping) {
                $reference = $fieldMapping['reference'];

                if (!in_array($reference, array_keys($this->validReferences))) {
                    throw new InvalidMappingException(
                        $reference .
                        ' is not a valid reference, valid references are: ' .
                        implode(', ', array_keys($this->validReferences))
                    );
                }

                $config[$reference][$field] = array(
                    'field' => $field,
                    'type'  => $fieldMapping['type'],
                    'class' => $fieldMapping['class'],
                );

                if (array_key_exists('mappedBy', $fieldMapping)) {
                    $config[$reference][$field]['mappedBy'] = $fieldMapping['mappedBy'];

                }

                if (array_key_exists('identifier', $fieldMapping)) {
                    $config[$reference][$field]['identifier'] = $fieldMapping['identifier'];

                }

                if (array_key_exists('inversedBy', $fieldMapping)) {
                    $config[$reference][$field]['inversedBy'] = $fieldMapping['inversedBy'];
                }
            }
        }
        $config = array_merge($this->validReferences, $config);
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}

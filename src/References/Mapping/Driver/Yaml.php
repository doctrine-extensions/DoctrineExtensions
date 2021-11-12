<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * @var array
     */
    private $validReferences = [
        'referenceOne' => [],
        'referenceMany' => [],
        'referenceManyEmbed' => [],
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->getName());

        if (isset($mapping['gedmo'], $mapping['gedmo']['reference'])) {
            foreach ($mapping['gedmo']['reference'] as $field => $fieldMapping) {
                $reference = $fieldMapping['reference'];

                if (!in_array($reference, array_keys($this->validReferences), true)) {
                    throw new InvalidMappingException($reference.' is not a valid reference, valid references are: '.implode(', ', array_keys($this->validReferences)));
                }

                $config[$reference][$field] = [
                    'field' => $field,
                    'type' => $fieldMapping['type'],
                    'class' => $fieldMapping['class'],
                ];

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

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}

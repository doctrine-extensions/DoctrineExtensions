<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Timestampable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * List of types which are valid for timestamp
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'date',
        'date_immutable',
        'time',
        'time_immutable',
        'datetime',
        'datetime_immutable',
        'datetimetz',
        'datetimetz_immutable',
        'timestamp',
        'vardatetime',
        'integer',
    ];

    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->getName());

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['timestampable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['timestampable'];
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'date', 'datetime' or 'time' in class - {$meta->getName()}");
                    }
                    if (!isset($mappingProperty['on']) || !in_array($mappingProperty['on'], ['update', 'create', 'change'], true)) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->getName()}");
                    }

                    if ('change' === $mappingProperty['on']) {
                        if (!isset($mappingProperty['field'])) {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->getName()}");
                        }
                        $trackedFieldAttribute = $mappingProperty['field'];
                        $valueAttribute = $mappingProperty['value'] ?? null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException('Timestampable extension does not support multiple value changeset detection yet.');
                        }
                        $field = [
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        ];
                    }
                    $config[$mappingProperty['on']][] = $field;
                }
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], self::VALID_TYPES, true);
    }
}

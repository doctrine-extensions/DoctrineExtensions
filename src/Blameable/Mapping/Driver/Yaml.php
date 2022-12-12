<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * List of types which are valid for blameable
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'one',
        'string',
        'int',
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
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['blameable'];
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' or a reference in class - {$meta->getName()}");
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
                            throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
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

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['blameable'];
                    if (!$meta->isSingleValuedAssociation($field)) {
                        throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a one-to-many relation or a string field - {$meta->getName()}");
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
                            throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
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

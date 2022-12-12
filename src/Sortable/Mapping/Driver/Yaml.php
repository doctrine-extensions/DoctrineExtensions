<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sortable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * List of types which are valid for position fields
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'int',
        'integer',
        'smallint',
        'bigint',
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
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('sortablePosition', $fieldMapping['gedmo'], true)) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                        }
                        $config['position'] = $field;
                    }
                }
            }
            $this->readSortableGroups($mapping['fields'], $config);
        }
        if (isset($mapping['manyToOne'])) {
            $this->readSortableGroups($mapping['manyToOne'], $config);
        }
        if (isset($mapping['manyToMany'])) {
            $this->readSortableGroups($mapping['manyToMany'], $config);
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (!isset($config['position'])) {
                throw new InvalidMappingException("Missing property: 'position' in class - {$meta->getName()}");
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    /**
     * Checks if $field type is valid as SortablePosition field
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

    private function readSortableGroups(iterable $mapping, array &$config): void
    {
        foreach ($mapping as $field => $fieldMapping) {
            if (isset($fieldMapping['gedmo'])) {
                if (in_array('sortableGroup', $fieldMapping['gedmo'], true)) {
                    if (!isset($config['groups'])) {
                        $config['groups'] = [];
                    }
                    $config['groups'][] = $field;
                }
            }
        }
    }
}

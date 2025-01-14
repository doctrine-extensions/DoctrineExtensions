<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Sluggable
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
     * List of types which are valid for slug and sluggable fields
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'string',
        'text',
        'integer',
        'int',
        'datetime',
        'citext',
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
                $config = $this->buildFieldConfiguration($field, $fieldMapping, $meta, $config);
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $overrideMapping) {
                $config = $this->buildFieldConfiguration($field, $overrideMapping, $meta, $config);
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    /**
     * Checks if $field type is valid as Sluggable field
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping->type ?? $mapping['type'], self::VALID_TYPES, true);
    }

    /**
     * @param array<string, mixed>  $fieldMapping
     * @param ClassMetadata<object> $meta
     * @param array<string, mixed>  $config
     *
     * @return array<string, mixed>
     */
    private function buildFieldConfiguration(string $field, array $fieldMapping, ClassMetadata $meta, array $config): array
    {
        if (isset($fieldMapping['gedmo'])) {
            if (isset($fieldMapping['gedmo']['slug'])) {
                $slug = $fieldMapping['gedmo']['slug'];
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->getName()}");
                }
                // process slug handlers
                $handlers = [];
                if (isset($slug['handlers'])) {
                    foreach ($slug['handlers'] as $handlerClass => $options) {
                        if (!strlen($handlerClass)) {
                            throw new InvalidMappingException("SlugHandler class: {$handlerClass} should be a valid class name in entity - {$meta->getName()}");
                        }
                        $handlers[$handlerClass] = $options;
                        $handlerClass::validate($handlers[$handlerClass], $meta);
                    }
                }
                // process slug fields
                if (empty($slug['fields']) || !is_array($slug['fields'])) {
                    throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->getName()}");
                }
                foreach ($slug['fields'] as $slugField) {
                    if (!$meta->hasField($slugField)) {
                        throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->getName()}");
                    }
                    if (!$this->isValidField($meta, $slugField)) {
                        throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->getName()}");
                    }
                }

                $config['slugs'][$field]['fields'] = $slug['fields'];
                $config['slugs'][$field]['handlers'] = $handlers;
                $config['slugs'][$field]['slug'] = $field;
                $config['slugs'][$field]['style'] = isset($slug['style']) ?
                    (string) $slug['style'] : 'default';

                $config['slugs'][$field]['dateFormat'] = isset($slug['dateFormat']) ?
                    (string) $slug['dateFormat'] : 'Y-m-d-H:i';

                $config['slugs'][$field]['updatable'] = isset($slug['updatable']) ?
                    (bool) $slug['updatable'] : true;

                $config['slugs'][$field]['unique'] = isset($slug['unique']) ?
                    (bool) $slug['unique'] : true;

                $config['slugs'][$field]['unique_base'] = $slug['unique_base'] ?? null;

                $config['slugs'][$field]['separator'] = isset($slug['separator']) ?
                    (string) $slug['separator'] : '-';

                $config['slugs'][$field]['prefix'] = isset($slug['prefix']) ?
                    (string) $slug['prefix'] : '';

                $config['slugs'][$field]['suffix'] = isset($slug['suffix']) ?
                    (string) $slug['suffix'] : '';

                if (!$meta->isMappedSuperclass && $meta->isIdentifier($field) && !$config['slugs'][$field]['unique']) {
                    throw new InvalidMappingException("Identifier field - [{$field}] slug must be unique in order to maintain primary key in class - {$meta->getName()}");
                }
                $ubase = $config['slugs'][$field]['unique_base'];
                if (false === $config['slugs'][$field]['unique'] && $ubase) {
                    throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
                }
                if ($ubase && !$meta->hasField($ubase) && !$meta->hasAssociation($ubase)) {
                    throw new InvalidMappingException("Unable to find [{$ubase}] as mapped property in entity - {$meta->getName()}");
                }
            }
        }

        return $config;
    }
}

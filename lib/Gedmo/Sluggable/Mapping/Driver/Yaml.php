<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
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
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validTypes = array(
        'string',
        'text',
        'integer',
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {}

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (isset($fieldMapping['gedmo']['slug'])) {
                        $slug = $fieldMapping['gedmo']['slug'];
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                        }
                        // process slug handlers
                        $handlers = array();
                        if (isset($slug['handlers'])) {
                            foreach ($slug['handlers'] as $handlerClass => $options) {
                                if (!strlen($handlerClass)) {
                                    throw new InvalidMappingException("SlugHandler class: {$handlerClass} should be a valid class name in entity - {$meta->name}");
                                }
                                $handlers[$handlerClass] = $options;
                                $handlerClass::validate($handlers[$handlerClass], $meta);
                            }
                        }
                        // process slug fields
                        if (empty($slug['fields']) || !is_array($slug['fields'])) {
                            throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->name}");
                        }
                        foreach ($slug['fields'] as $slugField) {
                            if (!$meta->hasField($slugField) || $meta->isInheritedField($slugField)) {
                                throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->name}");
                            }
                            if (!$this->isValidField($meta, $slugField)) {
                                throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                            }
                        }
                        $config['slugs'][$field]['fields'] = $slug['fields'];
                        $config['slugs'][$field]['handlers'] = $handlers;
                        $config['slugs'][$field]['slug'] = $field;
                        $config['slugs'][$field]['style'] = isset($slug['style']) ?
                            (string)$slug['style'] : 'default';

                        $config['slugs'][$field]['updatable'] = isset($slug['updatable']) ?
                            (bool)$slug['updatable'] : true;

                        $config['slugs'][$field]['unique'] = isset($slug['unique']) ?
                            (bool)$slug['unique'] : true;

                        $config['slugs'][$field]['separator'] = isset($slug['separator']) ?
                            (string)$slug['separator'] : '-';
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
        return \Symfony\Component\Yaml\Yaml::load($file);
    }

    /**
     * Checks if $field type is valid as Sluggable field
     *
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField(ClassMetadata $meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}

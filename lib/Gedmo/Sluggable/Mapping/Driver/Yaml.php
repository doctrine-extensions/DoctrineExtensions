<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
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
        'string'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config)
    {
        if ($config && !isset($config['fields'])) {
            throw new InvalidMappingException("Unable to find any sluggable fields specified for Sluggable entity - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {

                    if (in_array('sluggable', $fieldMapping['gedmo'])) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        $sluggable = $fieldMapping['gedmo'][array_search('sluggable', $fieldMapping['gedmo'])];
                        $config['fields'][] = array('field' => $field, 'position' => $sluggable['position']);
                    } elseif (isset($fieldMapping['gedmo']['slug']) || in_array('slug', $fieldMapping['gedmo'])) {
                        $slug = $fieldMapping['gedmo']['slug'];
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        if (isset($config['slug'])) {
                            throw new InvalidMappingException("There cannot be two slug fields: [{$slugField}] and [{$config['slug']}], in class - {$meta->name}.");
                        }

                        $config['slug'] = $field;
                        $config['style'] = isset($slug['style']) ?
                            (string)$slug['style'] : 'default';

                        $config['updatable'] = isset($slug['updatable']) ?
                            (bool)$slug['updatable'] : true;

                        $config['unique'] = isset($slug['unique']) ?
                            (bool)$slug['unique'] : true;

                        $config['separator'] = isset($slug['separator']) ?
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
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}

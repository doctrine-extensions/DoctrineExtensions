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
        'text'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config) {
            if (!isset($config['fields'])) {
                throw new InvalidMappingException("Unable to find any sluggable fields specified for Sluggable entity - {$meta->name}");
            }
            foreach ($config['fields'] as $slugField => $fields) {
                if (!isset($config['slugFields'][$slugField])) {
                    throw new InvalidMappingException("Unable to find {$slugField} slugField specified for Sluggable entity - {$meta->name}, you should specify slugField annotation property");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {

                    if (isset($fieldMapping['gedmo']['sluggable']) || in_array('sluggable', $fieldMapping['gedmo'])) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        $sluggable = (isset($fieldMapping['gedmo']['sluggable'])? $fieldMapping['gedmo']['sluggable']:array());
                        $slugField = (isset($sluggable['slugField'])? $sluggable['slugField']:'slug');
                        $position = (isset($sluggable['position'])? $sluggable['position']:0);
                        $config['fields'][$slugField][] = array('field' => $field, 'position' => $position, 'slugField' => $slugField);
                    } elseif (isset($fieldMapping['gedmo']['slug']) || in_array('slug', $fieldMapping['gedmo'])) {
                        $slug = isset($fieldMapping['gedmo']['slug']) ? $fieldMapping['gedmo']['slug'] : array();
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        $config['slugFields'][$field]['slug'] = $field;
                        $config['slugFields'][$field]['style'] = isset($slug['style']) ?
                            (string)$slug['style'] : 'default';

                        $config['slugFields'][$field]['updatable'] = isset($slug['updatable']) ?
                            (bool)$slug['updatable'] : true;

                        $config['slugFields'][$field]['unique'] = isset($slug['unique']) ?
                            (bool)$slug['unique'] : true;

                        $config['slugFields'][$field]['separator'] = isset($slug['separator']) ?
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

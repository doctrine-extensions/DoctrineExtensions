<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends File
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.xml';

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
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config && !isset($config['fields'])) {
            throw new InvalidMappingException("Unable to find any sluggable fields specified for Sluggable entity - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config) {
        $xml = $this->_getMapping($meta->name);

        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $field = (string)$mapping['name'];
                if (isset($mapping->gedmo)) {
                    if (isset($mapping->gedmo->sluggable)) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                        }
                        $options = array('position' => false, 'field' => $field);
                        if (isset($mapping->gedmo->sluggable['position'])) {
                            $options['position'] = (int)$mapping->gedmo->sluggable['position'];
                        }
                        $config['fields'][] = $options;
                    } elseif (isset($mapping->gedmo->slug)) {
                        $slug = $mapping->gedmo->slug;
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
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } else if (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = (string)$mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        }
        return $result;
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

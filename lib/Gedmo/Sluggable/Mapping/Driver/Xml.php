<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{

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
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);

        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->sluggable)) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                    }
                    $options = array('position' => false, 'field' => $field);
                    if ($this->_isAttributeSet($mapping->sluggable, 'position')) {
                        $options['position'] =  (int)$this->_getAttribute($mapping->sluggable, 'position');
                    }
                    $config['fields'][] = $options;
                } elseif (isset($mapping->slug)) {
                    /**
                     * @var \SimpleXmlElement $slug
                     */
                    $slug = $mapping->slug;
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                    }
                    if (isset($config['slug'])) {
                        throw new InvalidMappingException("There cannot be two slug fields: [{$slug}] and [{$config['slug']}], in class - {$meta->name}.");
                    }
                    $config['slug'] = $field;
                    $config['style'] = $this->_isAttributeSet($slug, 'style') ?
                        $this->_getAttribute($slug, 'style') : 'default';

                    $config['updatable'] = $this->_isAttributeSet($slug, 'updatable') ?
                        (bool)$this->_getAttribute($slug, 'updatable') : true;

                    $config['unique'] = $this->_isAttributeSet($slug, 'unique') ?
                        (bool)$this->_getAttribute($slug, 'unique') : true;

                    $config['separator'] = $this->_isAttributeSet($slug, 'separator') ?
                        $this->_getAttribute($slug, 'separator') : '-';
                }
            }
        }
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

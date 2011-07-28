<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
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

                    $options = array('position' => false, 'field' => $field, 'slugField' => 'slug');
                    if ($this->_isAttributeSet($mapping->sluggable, 'position')) {
                        $options['position'] =  (int)$this->_getAttribute($mapping->sluggable, 'position');
                    }

                    if ($this->_isAttributeSet($mapping->sluggable, 'slugField')) {
                        $options['slugField'] =  $this->_getAttribute($mapping->sluggable, 'slugField');
                    }

                    $config['fields'][$options['slugField']][] = $options;
                } elseif (isset($mapping->slug)) {
                    /**
                     * @var \SimpleXmlElement $slug
                     */
                    $slug = $mapping->slug;
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                    }
                    $config['slugFields'][$field]['slug'] = $field;
                    $config['slugFields'][$field]['style'] = $this->_isAttributeSet($slug, 'style') ?
                        $this->_getAttribute($slug, 'style') : 'default';

                    $config['slugFields'][$field]['updatable'] = $this->_isAttributeSet($slug, 'updatable') ?
                        (bool)$this->_getAttribute($slug, 'updatable') : true;

                    $config['slugFields'][$field]['unique'] = $this->_isAttributeSet($slug, 'unique') ?
                        (bool)$this->_getAttribute($slug, 'unique') : true;

                    $config['slugFields'][$field]['separator'] = $this->_isAttributeSet($slug, 'separator') ?
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
    protected function isValidField(ClassMetadata $meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}

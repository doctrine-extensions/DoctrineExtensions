<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'mapped-superclass')) {
            if (isset($xml->translation)) {
                /**
                 * @var \SimpleXmlElement $data
                 */
                $data = $xml->translation;
                if ($this->_isAttributeSet($data, 'locale')) {
                    $config['locale'] = $this->_getAttribute($data, 'locale');
                } elseif ($this->_isAttributeSet($data, 'language')) {
                    $config['locale'] = $this->_getAttribute($data, 'language');
                }
                if ($this->_isAttributeSet($data, 'entity')) {
                    $entity = $this->_getAttribute($data, 'entity');
                    if (!class_exists($entity)) {
                        throw new InvalidMappingException("Translation entity class: {$entity} does not exist.");
                    }
                    $config['translationClass'] = $entity;
                }
            }
        }

        if (isset($xmlDoctrine->field)) {
            foreach ($xmlDoctrine->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);
                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->translatable)) {
                    $config['fields'][] = $field;
                    /** @var \SimpleXmlElement $data */
                    $data = $mapping->translatable;
                    if ($this->_isAttributeSet($data, 'fallback')) {
                        $config['fallback'][$field] = 'true' == $this->_getAttribute($data, 'fallback') ? true : false;
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
        }
    }
}

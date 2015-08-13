<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Translatable
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
    public function readExtendedMetadata($meta, array &$config)
    {
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
                    if (!$cl = $this->getRelatedClassName($meta, $entity)) {
                        throw new InvalidMappingException("Translation entity class: {$entity} does not exist.");
                    }
                    $config['translationClass'] = $cl;
                }
            }
        }

        if (property_exists($meta, 'embeddedClasses') && $meta->embeddedClasses) {
            foreach ($meta->embeddedClasses as $propertyName => $embeddedClassInfo) {
                $xmlEmbbededClass = $this->_getMapping($embeddedClassInfo['class']);
                $this->inspectElementsForTranslatableFields($xmlEmbbededClass, $config, $propertyName);
            }
        }

        $this->inspectElementsForTranslatableFields($xmlDoctrine, $config);

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
        }
    }

    private function inspectElementsForTranslatableFields(\SimpleXMLElement $xml, array &$config, $prefix = null)
    {
        if (!isset($xml->field)) {
            return;
        }

        foreach ($xml->field as $mapping) {
            $mappingDoctrine = $mapping;
            /**
             * @var \SimpleXmlElement $mapping
             */
            $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);
            $field = null !== $prefix ? $prefix . '.' . $this->_getAttribute($mappingDoctrine, 'name') : $this->_getAttribute($mappingDoctrine, 'name');
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
}

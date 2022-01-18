<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 */
class Xml extends BaseXml
{
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->getName());
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (('entity' === $xmlDoctrine->getName() || 'mapped-superclass' === $xmlDoctrine->getName())) {
            if ($xml->count() && isset($xml->translation)) {
                /**
                 * @var \SimpleXmlElement
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
                if ($meta->isInheritedEmbeddedClass($propertyName)) {
                    continue;
                }
                $xmlEmbeddedClass = $this->_getMapping($embeddedClassInfo['class']);
                $this->inspectElementsForTranslatableFields($xmlEmbeddedClass, $config, $propertyName);
            }
        }

        if ($xmlDoctrine->{'attribute-overrides'}->count() > 0) {
            foreach ($xmlDoctrine->{'attribute-overrides'}->{'attribute-override'} as $overrideMapping) {
                $this->buildFieldConfiguration($this->_getAttribute($overrideMapping, 'name'), $overrideMapping->field, $config);
            }
        }

        $this->inspectElementsForTranslatableFields($xmlDoctrine, $config);

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->getName()}");
            }
        }
    }

    private function inspectElementsForTranslatableFields(\SimpleXMLElement $xml, array &$config, ?string $prefix = null): void
    {
        if (!isset($xml->field)) {
            return;
        }

        foreach ($xml->field as $mapping) {
            $mappingDoctrine = $mapping;

            $fieldName = $this->_getAttribute($mappingDoctrine, 'name');
            if (null !== $prefix) {
                $fieldName = $prefix.'.'.$fieldName;
            }
            $this->buildFieldConfiguration($fieldName, $mapping, $config);
        }
    }

    private function buildFieldConfiguration(string $fieldName, \SimpleXMLElement $mapping, array &$config): void
    {
        $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);
        if ($mapping->count() > 0 && isset($mapping->translatable)) {
            $config['fields'][] = $fieldName;
            /** @var \SimpleXmlElement $data */
            $data = $mapping->translatable;
            if ($this->_isAttributeSet($data, 'fallback')) {
                $config['fallback'][$fieldName] = $this->_getBooleanAttribute($data, 'fallback');
            }
        }
    }
}

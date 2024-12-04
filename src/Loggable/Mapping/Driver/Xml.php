<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ClassMetadataODM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 *
 * @internal
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

        if (in_array($xmlDoctrine->getName(), ['mapped-superclass', 'entity', 'document'], true)) {
            if (isset($xml->loggable)) {
                /**
                 * @var \SimpleXMLElement
                 */
                $data = $xml->loggable;
                $config['loggable'] = true;
                if ($this->_isAttributeSet($data, 'log-entry-class')) {
                    $class = $this->_getAttribute($data, 'log-entry-class');
                    if (!$cl = $this->getRelatedClassName($meta, $class)) {
                        throw new InvalidMappingException("LogEntry class: {$class} does not exist.");
                    }
                    $config['logEntryClass'] = $cl;
                }
            }
        }

        if (isset($xmlDoctrine->field)) {
            $config = $this->inspectElementForVersioned($xmlDoctrine->field, $config, $meta);
        }
        foreach ($xmlDoctrine->{'attribute-overrides'}->{'attribute-override'} ?? [] as $overrideMapping) {
            $config = $this->inspectElementForVersioned($overrideMapping, $config, $meta);
        }
        if (isset($xmlDoctrine->{'many-to-one'})) {
            $config = $this->inspectElementForVersioned($xmlDoctrine->{'many-to-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'one-to-one'})) {
            $config = $this->inspectElementForVersioned($xmlDoctrine->{'one-to-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'reference-one'})) {
            $config = $this->inspectElementForVersioned($xmlDoctrine->{'reference-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'embedded'})) {
            $config = $this->inspectElementForVersioned($xmlDoctrine->{'embedded'}, $config, $meta);
        }

        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof ClassMetadataODM && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->getName()}");
            }
            if (isset($config['versioned']) && !isset($config['loggable'])) {
                throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->getName()}");
            }
        }

        return $config;
    }

    /**
     * Searches mappings on element for versioned fields
     *
     * @param array<string, mixed>  $config
     * @param ClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, array $config, ClassMetadata $meta): array
    {
        foreach ($element as $mapping) {
            $mappingDoctrine = $mapping;
            /**
             * @var \SimpleXmlElement
             */
            $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

            $isAssoc = $this->_isAttributeSet($mappingDoctrine, 'field');
            $field = $this->_getAttribute($mappingDoctrine, $isAssoc ? 'field' : 'name');

            if (isset($mapping->versioned)) {
                if ($isAssoc && !$meta->associationMappings[$field]['isOwningSide']) {
                    throw new InvalidMappingException("Cannot version [{$field}] as it is not the owning side in object - {$meta->getName()}");
                }
                $config['versioned'][] = $field;
            }
        }

        return $config;
    }
}

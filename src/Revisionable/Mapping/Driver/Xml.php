<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBDOMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * XML mapping driver for the revisionable extension.
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
        /** @var \SimpleXMLElement $xml */
        $xml = $this->_getMapping($meta->getName());
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (in_array($xmlDoctrine->getName(), ['mapped-superclass', 'entity', 'document'], true)) {
            if (isset($xml->revisionable)) {
                /** @var \SimpleXMLElement $data */
                $data = $xml->revisionable;
                $config['revisionable'] = true;

                if ($this->_isAttributeSet($data, 'revision-class')) {
                    $class = $this->_getAttribute($data, 'revision-class');

                    if (!$cl = $this->getRelatedClassName($meta, $class)) {
                        throw new InvalidMappingException(sprintf("Class '%s' does not exist.", $class));
                    }

                    $config['revisionClass'] = $cl;
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
            if ($meta instanceof MongoDBDOMClassMetadata && is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException(sprintf("Composite identifiers are not supported by the revisionable extension when using doctrine/mongodb-odm, cannot keep revisions of '%s'.", $meta->getName()));
            }

            // Invalid when the versioned config is set and the revisionable flag has not been set
            if (isset($config['versioned']) && !isset($config['revisionable'])) {
                throw new InvalidMappingException(sprintf("Class '%s' has fields with the 'gedmo:versioned' element but the class does not have the 'gedmo:revisionable' element.", $meta->getName()));
            }

            // Invalid when using the ORM and the object is an embedded class
            if ($meta instanceof ORMClassMetadata && isset($config['revisionable']) && $meta->isEmbeddedClass) {
                throw new InvalidMappingException(sprintf("Class '%s' is an embedded class and cannot have the 'gedmo:revisionable' element.", $meta->getName()));
            }
        }

        return $config;
    }

    /**
     * Searches mappings on element for versioned fields
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, array $config, ClassMetadata $meta): array
    {
        foreach ($element as $mapping) {
            $mappingDoctrine = $mapping;

            /** @var \SimpleXMLElement $mapping */
            $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

            if (isset($mapping->versioned)) {
                $isAssoc = $this->_isAttributeSet($mappingDoctrine, 'field');
                $field = $this->_getAttribute($mappingDoctrine, $isAssoc ? 'field' : 'name');

                if ($isAssoc && !$meta->associationMappings[$field]['isOwningSide']) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, it is not the owning side of the relationship.', $meta->getName(), $field));
                }

                $config['versioned'][] = $field;
            }
        }

        return $config;
    }
}

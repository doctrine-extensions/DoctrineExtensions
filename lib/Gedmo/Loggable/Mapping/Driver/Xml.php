<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Loggable\Mapping\LoggableMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a xml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $xml = $this->getMapping($meta->name);
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if ($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'document' || $xmlDoctrine->getName() == 'mapped-superclass') {
            if (isset($xml->loggable)) {
                $data = $xml->loggable;
                if ($this->isAttributeSet($data, 'log-entry-class')) {
                    if (!class_exists($name = $this->getAttribute($data, 'log-entry-class'))) {
                        $ns = substr($meta->name, 0, strrpos($meta->name, '\\'));
                        if (!class_exists($name = $ns.'\\'.$name)) {
                            throw new InvalidMappingException("LogEntry class: ".$this->_getAttribute($data, 'log-entry-class')." does not exist.");
                        }
                    }
                    $exm->setLogClass($name);
                }
            }
        }

        if (isset($xmlDoctrine->field)) {
            $this->inspectElementForVersioned($xmlDoctrine->field, $exm, $meta);
        }
        if (isset($xmlDoctrine->{'many-to-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'many-to-one'}, $exm, $meta);
        }
        if (isset($xmlDoctrine->{'one-to-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'one-to-one'}, $exm, $meta);
        }
        if (isset($xmlDoctrine->{'reference-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'reference-one'}, $exm, $meta);
        }
    }

    /**
     * Searches mappings on element for versioned fields
     *
     * @param \SimpleXMLElement $element
     * @param \Gedmo\Loggable\Mapping\LoggableMetadata $exm
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, LoggableMetadata $exm, ClassMetadata $meta)
    {
        foreach ($element as $mapping) {
            $mappingDoctrine = $mapping;
            $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

            $isAssoc = $this->isAttributeSet($mappingDoctrine, 'field');
            $field = $this->getAttribute($mappingDoctrine, $isAssoc ? 'field' : 'name');

            if (isset($mapping->versioned)) {
                $exm->map($field);
            }
        }
    }
}

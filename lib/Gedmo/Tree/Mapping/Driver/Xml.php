<?php

namespace Gedmo\Tree\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is a xml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Tree
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
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
        $xmlDoctrine = $this->getMapping($meta->name);
        $xml = $xmlDoctrine->children(self::GEDMO_NAMESPACE_URI);
        $mapping = array();

        if (isset($xml->tree) && $this->isAttributeSet($xml->tree, 'type')) {
            $mapping['strategy'] = $this->getAttribute($xml->tree, 'type');
            $mapping['rootClass'] = $meta->isMappedSuperclass ? null : $meta->name;

            if ($lockingTimeout = $this->getAttribute($xml->tree, 'locking-timeout')) {
                $mapping['lock_timeout'] = intval($lockingTimeout);
            }
        }
        if (isset($xml->{'tree-closure'}) && $this->isAttributeSet($xml->{'tree-closure'}, 'class')) {
            $class = $this->getAttribute($xml->{'tree-closure'}, 'class');
            if (!class_exists($name = $class)) {
                if (!class_exists($name = $meta->reflClass->getNamespaceName().'\\'.$name)) {
                    throw new InvalidMappingException("Tree closure class: {$class} does not exist.");
                }
            }
            $mapping['closure'] = $name;
        }
        if (isset($xmlDoctrine->field)) {
            /** @var \SimpleXMLElement $mappingDoctrine */
            foreach ($xmlDoctrine->field as $mappingDoctrine) {
                $mapping = $mappingDoctrine->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->{'tree-left'})) {
                    $mapping['left'] = $field;
                } elseif (isset($mapping->{'tree-right'})) {
                    $mapping['right'] = $field;
                } elseif (isset($mapping->{'tree-root'})) {
                    $mapping['root'] = $field;
                } elseif (isset($mapping->{'tree-level'})) {
                    $mapping['level'] = $field;
                } elseif (isset($mapping->{'tree-path'})) {
                    $separator = $this->getAttribute($mapping->{'tree-path'}, 'separator');
                    $appendId = $this->getAttribute($mapping->{'tree-path'}, 'append_id');
                    if (!$appendId) {
                        $appendId = true;
                    } else {
                        $appendId = strtolower($appendId) === 'false' ? false : true;
                    }

                    $startsWithSeparator = $this->getAttribute($mapping->{'tree-path'}, 'starts_with_separator');
                    if (!$startsWithSeparator) {
                        $startsWithSeparator = false;
                    } else {
                        $startsWithSeparator = strtolower($startsWithSeparator) === 'false' ? false : true;
                    }

                    $endsWithSeparator = $this->getAttribute($mapping->{'tree-path'}, 'ends_with_separator');
                    if (!$endsWithSeparator) {
                        $endsWithSeparator = true;
                    } else {
                        $endsWithSeparator = strtolower($endsWithSeparator) == 'false' ? false : true;
                    }

                    $mapping['path'] = $field;
                    $mapping['path_separator'] = $separator;
                    $mapping['path_append_id'] = $appendId;
                    $mapping['path_starts_with_separator'] = $startsWithSeparator;
                    $mapping['path_ends_with_separator'] = $endsWithSeparator;
                } elseif (isset($mapping->{'tree-path-source'})) {
                    $mapping['path_source'] = $field;
                } elseif (isset($mapping->{'tree-lock-time'})) {
                    $mapping['lock'] = $field;
                }
            }
        }

        if ($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'mapped-superclass') {
            if (isset($xmlDoctrine->{'many-to-one'})) {
                /** @var \SimpleXMLElement $manyToOneMappingDoctrine */
                foreach ($xmlDoctrine->{'many-to-one'} as $manyToOneMappingDoctrine) {
                    $manyToOneMapping = $manyToOneMappingDoctrine->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($manyToOneMapping->{'tree-parent'})) {
                        $field = $this->getAttribute($manyToOneMappingDoctrine, 'field');
                        $targetEntity = $meta->associationMappings[$field]['targetEntity'];
                        $reflectionClass = new \ReflectionClass($targetEntity);
                        if ($targetEntity != $meta->name && !$reflectionClass->isSubclassOf($meta->name)) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $mapping['parent'] = $field;
                    }
                }
            }
        } elseif ($xmlDoctrine->getName() == 'document') {
            if (isset($xmlDoctrine->{'reference-one'})) {
                /** @var \SimpleXMLElement $referenceOneMappingDoctrine */
                foreach ($xmlDoctrine->{'reference-one'} as $referenceOneMappingDoctrine) {
                    $referenceOneMapping = $referenceOneMappingDoctrine->children(self::GEDMO_NAMESPACE_URI);
                    if (isset($referenceOneMapping->{'tree-parent'})) {
                        $field = $this->getAttribute($referenceOneMappingDoctrine, 'field');
                        if ($this->getAttribute($referenceOneMappingDoctrine, 'target-document') != $meta->name) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $mapping['parent'] = $field;
                    }
                }
            }
        }

        if ($mapping) {
            $exm->updateMapping($mapping);
        }
        if ($mapped = $exm->getMapping()) {
            // root class must be set
            if (!$exm->isEmpty() && !isset($mapped['rootClass']) && !$meta->isMappedSuperclass) {
                $exm->updateMapping(array('rootClass' => $meta->name));
            }
        }
    }
}

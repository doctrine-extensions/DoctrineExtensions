<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Driver;

use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBDOMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Revisionable;
use Gedmo\Mapping\Annotation\Versioned;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * Mapping driver for the revisionable extension which reads extended metadata from attributes on a revisionable class.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, Revisionable::class)) {
            \assert($annot instanceof Revisionable);

            $config['revisionable'] = true;

            if ($annot->revisionClass) {
                if (!$cl = $this->getRelatedClassName($meta, $annot->revisionClass)) {
                    throw new InvalidMappingException(sprintf("Class '%s' does not exist.", $annot->revisionClass));
                }

                $config['revisionClass'] = $cl;
            }
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            $field = $property->getName();

            // versioned property
            if ($this->reader->getPropertyAnnotation($property, Versioned::class)) {
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, %s implementations are not supported.', $meta->getName(), $field, ReadableCollection::class));
                }

                if ($meta instanceof ORMClassMetadata && isset($meta->embeddedClasses[$field])) {
                    $this->inspectEmbeddedForVersioned($field, $config, $meta);

                    continue;
                }

                // fields cannot be overridden and throws mapping exception
                if (!in_array($field, $config['versioned'] ?? [], true)) {
                    $config['versioned'][] = $field;
                }
            }
        }

        // Validate extension mapping
        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof MongoDBDOMClassMetadata && is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException(sprintf("Composite identifiers are not supported by the revisionable extension when using doctrine/mongodb-odm, cannot keep revisions of '%s'.", $meta->getName()));
            }

            // Invalid when the versioned config is set and the revisionable flag has not been set
            if (isset($config['versioned']) && !isset($config['revisionable'])) {
                throw new InvalidMappingException(sprintf("Class '%s' has '%s' annotated fields but is missing the '%s' class annotation.", $meta->getName(), Versioned::class, Revisionable::class));
            }
        }

        return $config;
    }

    /**
     * Searches properties of an embedded object for versioned fields.
     *
     * @param array<string, mixed> $config
     */
    private function inspectEmbeddedForVersioned(string $field, array &$config, ORMClassMetadata $meta): void
    {
        foreach ((new \ReflectionClass($meta->embeddedClasses[$field]['class']))->getProperties() as $property) {
            // versioned property
            if ($this->reader->getPropertyAnnotation($property, Versioned::class)) {
                $embeddedField = $field.'.'.$property->getName();
                $config['versioned'][] = $embeddedField;

                if (isset($meta->embeddedClasses[$embeddedField])) {
                    $this->inspectEmbeddedForVersioned($embeddedField, $config, $meta);
                }
            }
        }
    }
}

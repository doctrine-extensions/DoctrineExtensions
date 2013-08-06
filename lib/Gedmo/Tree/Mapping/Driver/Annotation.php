<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to define the tree type
     */
    const TREE = 'Gedmo\Mapping\Annotation\Tree';

    /**
     * Annotation to mark field as one which will store left value
     */
    const LEFT = 'Gedmo\Mapping\Annotation\TreeLeft';

    /**
     * Annotation to mark field as one which will store right value
     */
    const RIGHT = 'Gedmo\Mapping\Annotation\TreeRight';

    /**
     * Annotation to mark relative parent field
     */
    const PARENT = 'Gedmo\Mapping\Annotation\TreeParent';

    /**
     * Annotation to mark node level
     */
    const LEVEL = 'Gedmo\Mapping\Annotation\TreeLevel';

    /**
     * Annotation to mark field as tree root
     */
    const ROOT = 'Gedmo\Mapping\Annotation\TreeRoot';

    /**
     * Annotation to specify closure tree class
     */
    const CLOSURE = 'Gedmo\Mapping\Annotation\TreeClosure';

    /**
     * Annotation to specify path class
     */
    const PATH = 'Gedmo\Mapping\Annotation\TreePath';

    /**
     * Annotation to specify path source class
     */
    const PATH_SOURCE = 'Gedmo\Mapping\Annotation\TreePathSource';

    /**
     * Annotation to specify path hash class
     */
    const PATH_HASH = 'Gedmo\Mapping\Annotation\TreePathHash';

    /**
     * Annotation to mark the field to be used to hold the lock time
     */
    const LOCK_TIME = 'Gedmo\Mapping\Annotation\TreeLockTime';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        // class annotations
        $mapping = array();
        if ($annot = $this->reader->getClassAnnotation($class, self::TREE)) {
            $mapping['strategy'] = $annot->type;
            $mapping['rootClass'] = $meta->isMappedSuperclass ? null : $meta->name;
            $mapping['lock_timeout'] = intval($annot->lockingTimeout);
        }
        if ($annot = $this->reader->getClassAnnotation($class, self::CLOSURE)) {
            if ($annot->class) {
                if (!class_exists($name = $annot->class)) {
                    if (!class_exists($name = $class->getNamespaceName().'\\'.$name)) {
                        throw new InvalidMappingException("Tree closure class: {$annot->class} does not exist.");
                    }
                }
                $mapping['closure'] = $name;
            }
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // left
            if ($this->reader->getPropertyAnnotation($property, self::LEFT)) {
                $mapping['left'] = $property->getName();
            }
            // right
            if ($this->reader->getPropertyAnnotation($property, self::RIGHT)) {
                $mapping['right'] = $property->getName();
            }
            // ancestor/parent
            if ($this->reader->getPropertyAnnotation($property, self::PARENT)) {
                $mapping['parent'] = $property->getName();
            }
            // root
            if ($this->reader->getPropertyAnnotation($property, self::ROOT)) {
                $mapping['root'] = $property->getName();
            }
            // level
            if ($this->reader->getPropertyAnnotation($property, self::LEVEL)) {
                $mapping['level'] = $property->getName();
            }
            // path
            if ($pathAnnotation = $this->reader->getPropertyAnnotation($property, self::PATH)) {
                $mapping['path'] = $property->getName();
                $mapping['path_separator'] = $pathAnnotation->separator;
                $mapping['path_append_id'] = $pathAnnotation->appendId;
                $mapping['path_starts_with_separator'] = $pathAnnotation->startsWithSeparator;
                $mapping['path_ends_with_separator'] = $pathAnnotation->endsWithSeparator;
            }
            // path source
            if ($this->reader->getPropertyAnnotation($property, self::PATH_SOURCE)) {
                $mapping['path_source'] = $property->getName();
            }
            // path hash
            if ($this->reader->getPropertyAnnotation($property, self::PATH_HASH)) {
                $mapping['path_hash'] = $property->getName();
            }
            // lock
            if ($this->reader->getPropertyAnnotation($property, self::LOCK_TIME)) {
                $mapping['lock'] = $property->getName();
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

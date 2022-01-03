<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Tree;
use Gedmo\Mapping\Annotation\TreeClosure;
use Gedmo\Mapping\Annotation\TreeLeft;
use Gedmo\Mapping\Annotation\TreeLevel;
use Gedmo\Mapping\Annotation\TreeLockTime;
use Gedmo\Mapping\Annotation\TreeParent;
use Gedmo\Mapping\Annotation\TreePath;
use Gedmo\Mapping\Annotation\TreePathHash;
use Gedmo\Mapping\Annotation\TreePathSource;
use Gedmo\Mapping\Annotation\TreeRight;
use Gedmo\Mapping\Annotation\TreeRoot;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Tree\Mapping\Validator;

/**
 * This is an annotation mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define the tree type
     */
    public const TREE = Tree::class;

    /**
     * Annotation to mark field as one which will store left value
     */
    public const LEFT = TreeLeft::class;

    /**
     * Annotation to mark field as one which will store right value
     */
    public const RIGHT = TreeRight::class;

    /**
     * Annotation to mark relative parent field
     */
    public const PARENT = TreeParent::class;

    /**
     * Annotation to mark node level
     */
    public const LEVEL = TreeLevel::class;

    /**
     * Annotation to mark field as tree root
     */
    public const ROOT = TreeRoot::class;

    /**
     * Annotation to specify closure tree class
     */
    public const CLOSURE = TreeClosure::class;

    /**
     * Annotation to specify path class
     */
    public const PATH = TreePath::class;

    /**
     * Annotation to specify path source class
     */
    public const PATH_SOURCE = TreePathSource::class;

    /**
     * Annotation to specify path hash class
     */
    public const PATH_HASH = TreePathHash::class;

    /**
     * Annotation to mark the field to be used to hold the lock time
     */
    public const LOCK_TIME = TreeLockTime::class;

    /**
     * List of tree strategies available
     *
     * @var array
     */
    protected $strategies = [
        'nested',
        'closure',
        'materializedPath',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        $validator = new Validator();
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::TREE)) {
            if (!in_array($annot->type, $this->strategies, true)) {
                throw new InvalidMappingException("Tree type: {$annot->type} is not available.");
            }
            $config['strategy'] = $annot->type;
            $config['activate_locking'] = $annot->activateLocking;
            $config['locking_timeout'] = (int) $annot->lockingTimeout;

            if ($config['locking_timeout'] < 1) {
                throw new InvalidMappingException('Tree Locking Timeout must be at least of 1 second.');
            }
        }
        if ($annot = $this->reader->getClassAnnotation($class, self::CLOSURE)) {
            if (!$cl = $this->getRelatedClassName($meta, $annot->class)) {
                throw new InvalidMappingException("Tree closure class: {$annot->class} does not exist.");
            }
            $config['closure'] = $cl;
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
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'left' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                }
                $config['left'] = $field;
            }
            // right
            if ($this->reader->getPropertyAnnotation($property, self::RIGHT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'right' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                }
                $config['right'] = $field;
            }
            // ancestor/parent
            if ($this->reader->getPropertyAnnotation($property, self::PARENT)) {
                $field = $property->getName();
                if (!$meta->isSingleValuedAssociation($field)) {
                    throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->getName()}");
                }
                $config['parent'] = $field;
            }
            // root
            if ($this->reader->getPropertyAnnotation($property, self::ROOT)) {
                $field = $property->getName();
                if (!$meta->isSingleValuedAssociation($field)) {
                    if (!$meta->hasField($field)) {
                        throw new InvalidMappingException("Unable to find 'root' - [{$field}] as mapped property in entity - {$meta->getName()}");
                    }

                    if (!$validator->isValidFieldForRoot($meta, $field)) {
                        throw new InvalidMappingException("Tree root field should be either a literal property ('integer' types or 'string') or a many-to-one association through root field - [{$field}] in class - {$meta->getName()}");
                    }
                }
                $annotation = $this->reader->getPropertyAnnotation($property, self::ROOT);
                $config['rootIdentifierMethod'] = $annotation->identifierMethod;
                $config['root'] = $field;
            }
            // level
            if ($this->reader->getPropertyAnnotation($property, self::LEVEL)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'level' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                }
                $config['level'] = $field;
            }
            // path
            if ($pathAnnotation = $this->reader->getPropertyAnnotation($property, self::PATH)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'path' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidFieldForPath($meta, $field)) {
                    throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->getName()}");
                }
                if (strlen($pathAnnotation->separator) > 1) {
                    throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$pathAnnotation->separator} is invalid. It must be only one character long.");
                }
                $config['path'] = $field;
                $config['path_separator'] = $pathAnnotation->separator;
                $config['path_append_id'] = $pathAnnotation->appendId;
                $config['path_starts_with_separator'] = $pathAnnotation->startsWithSeparator;
                $config['path_ends_with_separator'] = $pathAnnotation->endsWithSeparator;
            }
            // path source
            if ($this->reader->getPropertyAnnotation($property, self::PATH_SOURCE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'path_source' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidFieldForPathSource($meta, $field)) {
                    throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->getName()}");
                }
                $config['path_source'] = $field;
            }

            // path hash
            if ($this->reader->getPropertyAnnotation($property, self::PATH_HASH)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'path_hash' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidFieldForPathHash($meta, $field)) {
                    throw new InvalidMappingException("Tree PathHash field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->getName()}");
                }
                $config['path_hash'] = $field;
            }
            // lock time

            if ($this->reader->getPropertyAnnotation($property, self::LOCK_TIME)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'lock_time' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$validator->isValidFieldForLockTime($meta, $field)) {
                    throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It must be \"date\" in class - {$meta->getName()}");
                }
                $config['lock_time'] = $field;
            }
        }

        if (isset($config['activate_locking']) && $config['activate_locking'] && !isset($config['lock_time'])) {
            throw new InvalidMappingException('You need to map a date field as the tree lock time field to activate locking support.');
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (isset($config['strategy'])) {
                if (is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                    throw new InvalidMappingException("Tree does not support composite identifiers in class - {$meta->getName()}");
                }
                $method = 'validate'.ucfirst($config['strategy']).'TreeMetadata';
                $validator->$method($meta, $config);
            } else {
                throw new InvalidMappingException("Cannot find Tree type for class: {$meta->getName()}");
            }
        }
    }
}

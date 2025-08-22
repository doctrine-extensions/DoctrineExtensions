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
 * Mapping driver for the tree extension which reads extended metadata from attributes on class which is part of a tree.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 * @author Kevin Mian Kraiker <kevin.mian@gmail.com>
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    /**
     * Mapping object to configure the type of tree.
     */
    public const TREE = Tree::class;

    /**
     * Mapping object to mark the field which will store the left value of a tree node.
     */
    public const LEFT = TreeLeft::class;

    /**
     * Mapping object to mark the field which will store the right value of a tree node.
     */
    public const RIGHT = TreeRight::class;

    /**
     * Mapping object to mark the field which will store the reference to the parent of a tree node.
     */
    public const PARENT = TreeParent::class;

    /**
     * Mapping object to mark the field which will store the level of a tree node.
     */
    public const LEVEL = TreeLevel::class;

    /**
     * Mapping object to mark the field which will store the reference to the root of a tree node.
     */
    public const ROOT = TreeRoot::class;

    /**
     * Mapping object to configure a closure tree object.
     */
    public const CLOSURE = TreeClosure::class;

    /**
     * Mapping object to configure a tree path field.
     */
    public const PATH = TreePath::class;

    /**
     * Mapping object to specify the source for a tree path.
     */
    public const PATH_SOURCE = TreePathSource::class;

    /**
     * Mapping object to configure the hash for a tree path.
     */
    public const PATH_HASH = TreePathHash::class;

    /**
     * Mapping object to configure the lock time for a tree.
     */
    public const LOCK_TIME = TreeLockTime::class;

    /**
     * List of tree strategies available
     *
     * @var string[]
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
            \assert($annot instanceof Tree);

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
            \assert($annot instanceof TreeClosure);

            if (!$cl = $this->getRelatedClassName($meta, $annot->class)) {
                throw new InvalidMappingException("Tree closure class: {$annot->class} does not exist.");
            }

            $config['closure'] = $cl;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()
                || $meta->isInheritedField($property->name)
                || isset($meta->associationMappings[$property->name]['inherited'])
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
            if ($annot = $this->reader->getPropertyAnnotation($property, self::ROOT)) {
                \assert($annot instanceof TreeRoot);

                $field = $property->getName();

                if (!$meta->isSingleValuedAssociation($field)) {
                    if (!$meta->hasField($field)) {
                        throw new InvalidMappingException("Unable to find 'root' - [{$field}] as mapped property in entity - {$meta->getName()}");
                    }

                    if (!$validator->isValidFieldForRoot($meta, $field)) {
                        throw new InvalidMappingException("Tree root field should be either a literal property ('integer' types or 'string') or a many-to-one association through root field - [{$field}] in class - {$meta->getName()}");
                    }
                }

                $config['rootIdentifierMethod'] = $annot->identifierMethod;
                $config['root'] = $field;
            }

            // level
            if ($annot = $this->reader->getPropertyAnnotation($property, self::LEVEL)) {
                \assert($annot instanceof TreeLevel);

                $field = $property->getName();

                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'level' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }

                if (!$validator->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                }

                $config['level'] = $field;
                $config['level_base'] = (int) $annot->base;
            }

            // path
            if ($annot = $this->reader->getPropertyAnnotation($property, self::PATH)) {
                \assert($annot instanceof TreePath);

                $field = $property->getName();

                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'path' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }

                if (!$validator->isValidFieldForPath($meta, $field)) {
                    throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->getName()}");
                }

                if (strlen($annot->separator) > 1) {
                    throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$annot->separator} is invalid. It must be only one character long.");
                }

                $config['path'] = $field;
                $config['path_separator'] = $annot->separator;
                $config['path_append_id'] = $annot->appendId;
                $config['path_starts_with_separator'] = $annot->startsWithSeparator;
                $config['path_ends_with_separator'] = $annot->endsWithSeparator;
            }

            // path source
            if (null !== $this->reader->getPropertyAnnotation($property, self::PATH_SOURCE)) {
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
            if (null !== $this->reader->getPropertyAnnotation($property, self::PATH_HASH)) {
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
            if (null !== $this->reader->getPropertyAnnotation($property, self::LOCK_TIME)) {
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

        return $config;
    }
}

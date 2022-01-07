<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;
use Gedmo\Tree\Mapping\Validator;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $strategies = [
        'nested',
        'closure',
        'materializedPath',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->getName());
        $validator = new Validator();

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['tree']['type'])) {
                $strategy = $classMapping['tree']['type'];
                if (!in_array($strategy, $this->strategies, true)) {
                    throw new InvalidMappingException("Tree type: $strategy is not available.");
                }
                $config['strategy'] = $strategy;
                $config['activate_locking'] = $classMapping['tree']['activateLocking'] ?? false;
                $config['locking_timeout'] = isset($classMapping['tree']['lockingTimeout']) ?
                    (int) $classMapping['tree']['lockingTimeout'] : 3;

                if ($config['locking_timeout'] < 1) {
                    throw new InvalidMappingException('Tree Locking Timeout must be at least of 1 second.');
                }
            }
            if (isset($classMapping['tree']['closure'])) {
                if (!$class = $this->getRelatedClassName($meta, $classMapping['tree']['closure'])) {
                    throw new InvalidMappingException("Tree closure class: {$classMapping['tree']['closure']} does not exist.");
                }
                $config['closure'] = $class;
            }
        }

        if (isset($mapping['id'])) {
            foreach ($mapping['id'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treePathSource', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->getName()}");
                        }
                        $config['path_source'] = $field;
                    }
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treeLeft', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                        }
                        $config['left'] = $field;
                    } elseif (in_array('treeRight', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                        }
                        $config['right'] = $field;
                    } elseif (in_array('treeLevel', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                        }
                        $config['level'] = $field;
                    } elseif (in_array('treeRoot', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidFieldForRoot($meta, $field)) {
                            throw new InvalidMappingException("Tree root field - [{$field}] type is not valid and must be any of the 'integer' types or 'string' in class - {$meta->getName()}");
                        }
                        $config['root'] = $field;
                    } elseif (in_array('treePath', $fieldMapping['gedmo'], true) || isset($fieldMapping['gedmo']['treePath'])) {
                        if (!$validator->isValidFieldForPath($meta, $field)) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->getName()}");
                        }

                        $treePathInfo = $fieldMapping['gedmo']['treePath'] ?? $fieldMapping['gedmo'][array_search(
                                'treePath',
                                $fieldMapping['gedmo'],
                                true
                            )];

                        if (is_array($treePathInfo) && isset($treePathInfo['separator'])) {
                            $separator = $treePathInfo['separator'];
                        } else {
                            $separator = '|';
                        }

                        if (strlen($separator) > 1) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$separator} is invalid. It must be only one character long.");
                        }

                        if (is_array($treePathInfo) && isset($treePathInfo['appendId'])) {
                            $appendId = $treePathInfo['appendId'];
                        } else {
                            $appendId = null;
                        }

                        if (is_array($treePathInfo) && isset($treePathInfo['startsWithSeparator'])) {
                            $startsWithSeparator = $treePathInfo['startsWithSeparator'];
                        } else {
                            $startsWithSeparator = false;
                        }

                        if (is_array($treePathInfo) && isset($treePathInfo['endsWithSeparator'])) {
                            $endsWithSeparator = $treePathInfo['endsWithSeparator'];
                        } else {
                            $endsWithSeparator = true;
                        }

                        $config['path'] = $field;
                        $config['path_separator'] = $separator;
                        $config['path_append_id'] = $appendId;
                        $config['path_starts_with_separator'] = $startsWithSeparator;
                        $config['path_ends_with_separator'] = $endsWithSeparator;
                    } elseif (in_array('treePathSource', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->getName()}");
                        }
                        $config['path_source'] = $field;
                    } elseif (in_array('treePathHash', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathHash field - [{$field}] type is not valid and must be 'string' in class - {$meta->getName()}");
                        }
                        $config['path_hash'] = $field;
                    } elseif (in_array('treeLockTime', $fieldMapping['gedmo'], true)) {
                        if (!$validator->isValidFieldForLocktime($meta, $field)) {
                            throw new InvalidMappingException("Tree LockTime field - [{$field}] type is not valid. It must be \"date\" in class - {$meta->getName()}");
                        }
                        $config['lock_time'] = $field;
                    } elseif (in_array('treeParent', $fieldMapping['gedmo'], true)) {
                        $config['parent'] = $field;
                    }
                }
            }
        }

        if (isset($config['activate_locking']) && $config['activate_locking'] && !isset($config['lock_time'])) {
            throw new InvalidMappingException('You need to map a date|datetime|timestamp field as the tree lock time field to activate locking support.');
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo'])) {
                    if (in_array('treeParent', $relationMapping['gedmo'], true)) {
                        if (!$rel = $this->getRelatedClassName($meta, $relationMapping['targetEntity'])) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->getName()}");
                        }
                        $config['parent'] = $field;
                    }
                    if (in_array('treeRoot', $relationMapping['gedmo'], true)) {
                        if (!$rel = $this->getRelatedClassName($meta, $relationMapping['targetEntity'])) {
                            throw new InvalidMappingException("Unable to find root-descendant relation through root field - [{$field}] in class - {$meta->getName()}");
                        }
                        $config['root'] = $field;
                    }
                }
            }
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

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }
}

<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends FileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $data = $this->getMapping($meta->name);
        $mapping = array();
        if (isset($data['gedmo'])) {
            $classMapping = $data['gedmo'];
            if (isset($classMapping['tree']['type'])) {
                $mapping['strategy'] = $classMapping['tree']['type'];
                $mapping['rootClass'] = $meta->isMappedSuperclass ? null : $meta->name;

                if (isset($classMapping['tree']['lockingTimeout'])) {
                    $mapping['lock_timeout'] = intval($classMapping['tree']['lockingTimeout']);
                }
            }
            if (isset($classMapping['tree']['closure'])) {
                $class = $classMapping['tree']['closure'];
                if (!class_exists($name = $class)) {
                    if (!class_exists($name = $meta->reflClass->getNamespaceName().'\\'.$name)) {
                        throw new InvalidMappingException("Tree closure class: {$class} does not exist.");
                    }
                }
                $mapping['closure'] = $name;
            }
        }
        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treeLeft', $fieldMapping['gedmo']) || array_key_exists('treeLeft', $fieldMapping['gedmo'])) {
                        $mapping['left'] = $field;
                    } elseif (in_array('treeRight', $fieldMapping['gedmo']) || array_key_exists('treeRight', $fieldMapping['gedmo'])) {
                        $mapping['right'] = $field;
                    } elseif (in_array('treeLevel', $fieldMapping['gedmo']) || array_key_exists('treeLevel', $fieldMapping['gedmo'])) {
                        $mapping['level'] = $field;
                    } elseif (in_array('treeRoot', $fieldMapping['gedmo']) || array_key_exists('treeRoot', $fieldMapping['gedmo'])) {
                        $mapping['root'] = $field;
                    } elseif (in_array('treePath', $fieldMapping['gedmo']) || array_key_exists('treePath', $fieldMapping['gedmo'])) {
                        $treePathInfo = isset($fieldMapping['gedmo']['treePath']) ? $fieldMapping['gedmo']['treePath'] :
                            $fieldMapping['gedmo'][array_search('treePath', $fieldMapping['gedmo'])];

                        if (is_array($treePathInfo) && isset($treePathInfo['separator'])) {
                            $separator = $treePathInfo['separator'];
                        } else {
                            $separator = '|';
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

                        $mapping['path'] = $field;
                        $mapping['path_separator'] = $separator;
                        $mapping['path_append_id'] = $appendId;
                        $mapping['path_starts_with_separator'] = $startsWithSeparator;
                        $mapping['path_ends_with_separator'] = $endsWithSeparator;
                    } elseif (in_array('treePathSource', $fieldMapping['gedmo']) || array_key_exists('treePathSource', $fieldMapping['gedmo'])) {
                        $mapping['path_source'] = $field;
                    } elseif (in_array('treePathHash', $fieldMapping['gedmo']) || array_key_exists('treePathHash', $fieldMapping['gedmo'])) {
                        $mapping['path_hash'] = $field;
                    } elseif (in_array('treeLockTime', $fieldMapping['gedmo']) || array_key_exists('treeLockTime', $fieldMapping['gedmo'])) {
                        $mapping['lock'] = $field;
                    } elseif (in_array('treeParent', $fieldMapping['gedmo']) || array_key_exists('treeParent', $fieldMapping['gedmo'])) {
                        $mapping['parent'] = $field;
                    }
                }
            }
        }

        if (isset($data['manyToOne'])) {
            foreach ($data['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo'])) {
                    if (in_array('treeParent', $relationMapping['gedmo']) || array_key_exists('treeParent', $relationMapping['gedmo'])) {
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

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}

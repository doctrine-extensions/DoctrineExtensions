<?php

namespace Gedmo\Tree\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tree\Strategy;

/**
 * Extension metadata for Tree behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TreeMetadata implements ExtensionMetadataInterface
{
    /**
     * @var array
     */
    private $supportedStrategies = array(
        Strategy::NESTED,
        Strategy::CLOSURE,
        Strategy::MATERIALIZED_PATH,
    );

    /**
     * tree mapping data
     *
     * @var array
     */
    private $mapping = array();

    /**
     * Update tree mapping with new options
     *
     * @param array $mapping
     */
    public function updateMapping(array $mapping)
    {
        $this->mapping = array_merge($this->mapping, $mapping);
    }

    /**
     * Get tree mapping information
     *
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Get strategy
     *
     * @return string or null
     */
    public function getStrategy()
    {
        return isset($this->mapping['strategy']) ? $this->mapping['strategy'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            $this->mapping = array(); // reset all mapped fields so cache will be empty
            return;
        }
        if (!in_array($this->mapping['strategy'], $this->supportedStrategies, true)) {
            $valid = implode(', ', $this->supportedStrategies);
            throw new InvalidMappingException("Strategy '{$this->mapping['strategy']}' is not supported, choose one of: {$valid}");
        }
        // parent must be available in all cases:
        if (!isset($this->mapping['parent'])) {
            throw new InvalidMappingException("Tree parent field must be available in class - {$meta->name}");
        }
        if (!$meta->isSingleValuedAssociation($this->mapping['parent'])) {
            throw new InvalidMappingException("Tree parent must be a single valued association in class - {$meta->name}");
        }

        $validIntBased = array('integer', 'smallint', 'bigint', 'int');
        $validStrBased = array('string', 'text');
        $validDateBased = array('date', 'datetime', 'timestamp');

        // if level is present, validate it
        if (isset($this->mapping['level'])) {
            if (!$meta->hasField($lvl = $this->mapping['level'])) {
                throw new InvalidMappingException("Unable to find 'level' - [{$field}] as mapped property in class - {$meta->name}");
            }
            $mapping = $meta->getFieldMapping($lvl);
            if (!in_array($mapping['type'], $validIntBased)) {
                $valid = implode(', ', $validIntBased);
                throw new InvalidMappingException("Tree level field - [{$lvl}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
            }
        }
        switch ($this->mapping['strategy']) {
            case Strategy::NESTED:
                // left
                if (!isset($this->mapping['left']) || !$meta->hasField($lft = $this->mapping['left'])) {
                    throw new InvalidMappingException("Unable to find 'left' - [{$lft}] as mapped property in class - {$meta->name}");
                }
                $mapping = $meta->getFieldMapping($lft);
                if (!in_array($mapping['type'], $validIntBased)) {
                    $valid = implode(', ', $validIntBased);
                    throw new InvalidMappingException("Tree left field - [{$lft}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
                }
                // right
                if (!isset($this->mapping['right']) || !$meta->hasField($rgt = $this->mapping['right'])) {
                    throw new InvalidMappingException("Unable to find 'right' - [{$rgt}] as mapped property in class - {$meta->name}");
                }
                $mapping = $meta->getFieldMapping($rgt);
                if (!in_array($mapping['type'], $validIntBased)) {
                    $valid = implode(', ', $validIntBased);
                    throw new InvalidMappingException("Tree right field - [{$lft}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
                }
                // root
                if (isset($this->mapping['root'])) {
                    if (!$meta->hasField($root = $this->mapping['root'])) {
                        throw new InvalidMappingException("Unable to find 'root' - [{$root}] as mapped property in class - {$meta->name}");
                    }
                    $mapping = $meta->getFieldMapping($root);
                    $idMapping = $meta->getFieldMapping($meta->getSingleIdentifierFieldName());
                    if ($mapping['type'] !== $idMapping['type']) {
                        throw new InvalidMappingException("Tree root field - [{$root}] type '{$mapping['type']}' does not match identifier type {$idMapping['type']} - in class {$meta->name}");
                    }
                }
                break;
            case Strategy::CLOSURE:
                // closure class
                if (!isset($this->mapping['closure'])) {
                    throw new InvalidMappingException("Tree closure class must be specified in class - {$meta->name}");
                }
                break;
            case Strategy::MATERIALIZED_PATH:
                // locking
                if ($om instanceof DocumentManager && isset($this->mapping['lock_timeout']) && $this->mapping['lock_timeout']) {
                    if (($timeout = $this->mapping['lock_timeout']) < 1) {
                        throw new InvalidMappingException("Tree Locking Timeout duration must be at least 1 second long.");
                    }
                    if (!isset($this->mapping['lock']) || !$meta->hasField($lock = $this->mapping['lock'])) {
                        throw new InvalidMappingException("Unable to find Tree lock field as mapped property - in class {$meta->name}");
                    }
                    $mapping = $meta->getFieldMapping($lock);
                    if (!in_array($mapping['type'], $validDateBased)) {
                        $valid = implode(', ', $validDateBased);
                        throw new InvalidMappingException("Tree lock field - [{$lock}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
                    }
                }
                // path
                if (!isset($this->mapping['path']) || !$meta->hasField($path = $this->mapping['path'])) {
                    throw new InvalidMappingException("Unable to find Tree path field as mapped property - in class {$meta->name}");
                }
                $mapping = $meta->getFieldMapping($path);
                if (!in_array($mapping['type'], $validStrBased)) {
                    $valid = implode(', ', $validStrBased);
                    throw new InvalidMappingException("Tree path field - [{$path}] type is not valid and must be one of: {$valid} - in class {$meta->name}");
                }
                if (isset($this->mapping['path_separator']) && strlen($sep = $this->mapping['path_separator']) > 1) {
                    throw new InvalidMappingException("Tree Path field - [{$path}] Separator {$sep} is invalid. It must be only one character long - in class {$meta->name}");
                }
                // path source
                if (!isset($this->mapping['path_source']) || !$meta->hasField($src = $this->mapping['path_source'])) {
                    throw new InvalidMappingException("Unable to Tree path source field as mapped property - in class {$meta->name}");
                }
                $valid = array('id', 'integer', 'smallint', 'bigint', 'string', 'int', 'float');
                $mapping = $meta->getFieldMapping($src);
                if (!in_array($mapping['type'], $valid)) {
                    $valid = implode(', ', $valid);
                    throw new InvalidMappingException("Tree path source field - [{$src}] type is not valid and must be one of: {$valid} - in class {$meta->name}");
                }
                // path hash
                if (isset($this->mapping['path_hash'])) {
                    if (!$meta->hasField($hash = $this->mapping['path_hash'])) {
                        throw new InvalidMappingException("Unable to find Tree path hash field - {$hash} as mapped property - in class {$meta->name}");
                    }
                    $mapping = $meta->getFieldMapping($hash);
                    if ($mapping['type'] !== 'string') {
                        throw new InvalidMappingException("Tree path hash field - [{$hash}] type is not valid and must be string - in class {$meta->name}");
                    }
                }
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return !isset($this->mapping['strategy']); // strategy must be available
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->mapping = $data;
    }
}

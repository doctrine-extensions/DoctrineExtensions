<?php

namespace Gedmo\References\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for references behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ReferencesMetadata implements ExtensionMetadataInterface
{
    /**
     * List of refs and their options
     * in pairs - $field => $options
     *
     * @var array
     */
    private $refs = array();

    /**
     * Map a reference field
     *
     * @param string $type - reference type
     * @param string $field
     * @param array $options
     */
    public function map($type, $field, array $options)
    {
        $this->refs[$type][$field] = $options;
    }

    /**
     * Get all available references for $type
     *
     * @param string $type - reference type
     *
     * @return array
     */
    public function getReferencesOfType($type)
    {
        return isset($this->refs[$type]) ? $this->refs[$type] : array();
    }

    /**
     * Get mapping for reference $name of $type
     *
     * @param string $type
     * @param string $name - property name
     * @return array or null
     */
    public function getReferenceMapping($type, $name)
    {
        if (isset($this->refs[$type])) {
            foreach ($this->refs[$type] as $field => $options) {
                if ($field === $name) {
                    return $options;
                }
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->refs) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->refs;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->refs = $data;
    }
}

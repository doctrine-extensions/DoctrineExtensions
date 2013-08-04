<?php

namespace Gedmo\ReferenceIntegrity\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for ReferenceIntegrity behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ReferenceIntegrityMetadata implements ExtensionMetadataInterface
{
    const NULLIFY = 'nullify';
    const RESTRICT = 'restrict';

    /**
     * List of fields and their action
     * in pairs - $field => $action
     *
     * @var array
     */
    private $fields = array();

    /**
     * List of valid integrity actions
     *
     * @var array
     */
    private $validActions = array(
        self::RESTRICT,
        self::NULLIFY,
    );

    /**
     * Map a reference integrity field
     *
     * @param string $field
     * @param string $action
     */
    public function map($field, $action)
    {
        $this->fields[$field] = $action;
    }

    /**
     * Get all available field names
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->fields as $field => $action) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find [{$field}] as mapped property in class - {$meta->name}");
            }
            $mapping = $meta->getFieldMapping($field);
            if (!isset($mapping['mappedBy'])) {
                throw new InvalidMappingException(
                    sprintf(
                        "'mappedBy' should be set on '%s' in '%s'",
                        $field,
                        $meta->name
                    )
                );
            }
            if (!in_array($action, $this->validActions)) {
                $valid = implode(', ', $this->validActions);
                throw new InvalidMappingException("Field - [{$field}] must have a value set to one of: {$valid} in class - {$meta->name}");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->fields) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->fields = $data;
    }
}

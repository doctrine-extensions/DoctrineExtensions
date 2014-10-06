<?php

namespace Gedmo\IpTraceable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for ip traceable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class IpTraceableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of blames and their options
     * in pairs - $field => $options
     *
     * @var array
     */
    private $blames = array();

    /**
     * List of types which are valid for IPs fields
     *
     * @var array
     */
    private $validFieldTypes = array(
        'string',
    );

    /**
     * Map a blameable field
     *
     * @param string $field
     * @param array  $options
     */
    public function map($field, array $options)
    {
        $this->blames[$field] = $options;
    }

    /**
     * Get all available field names
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->blames);
    }

    /**
     * Get mapping options for ip traceable $field
     *
     * @param string $field
     *
     * @return array
     */
    public function getOptions($field)
    {
        return isset($this->blames[$field]) ? $this->blames[$field] : array();
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }

        foreach ($this->blames as $field => $options) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find ip traceable [{$field}] as mapped property in entity - {$meta->name}");
            }

            $mapping = $meta->getFieldMapping($field);
            if (!in_array($mapping['type'], $this->validFieldTypes)) {
                $valid = implode(', ', $this->validFieldTypes);
                throw new InvalidMappingException("Field - [{$field}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
            }

            if (!in_array($options['on'], $valid = array('update', 'create', 'change'))) {
                $valid = implode(', ', $valid);
                throw new InvalidMappingException("Field - [{$field}] trigger 'on' must be one of [{$valid}] in class - {$meta->name}");
            }
            if ($options['on'] === 'change') {
                if (!isset($options['field'])) {
                    throw new InvalidMappingException("Missing parameters on property - {$field}, 'field' must be set on [change] trigger in class - {$meta->name}");
                }
                if (is_array($options['field']) && isset($options['value'])) {
                    throw new InvalidMappingException("IpTraceable extension does not support multiple value changeset detection, in class - {$meta->name}.");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->blames) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->blames;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->blames = $data;
    }
}

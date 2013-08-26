<?php

namespace Gedmo\SoftDeleteable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for softdeletable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SoftDeleteableMetadata implements ExtensionMetadataInterface
{
    /**
     * Softdeleteable field
     *
     * @var string
     */
    private $field;

    /**
     * TimeAware will check when object should
     * be softdeleted
     *
     * @var boolean
     */
    private $timeAware;

    /**
     * @var array
     */
    private $validFieldTypes = array(
        'date',
        'time',
        'datetime',
        'datetimetz',
        'timestamp',
        'zenddate',
        'vardatetime',
        'integer'
    );

    /**
     * Map a softdeleteable field
     *
     * @param string $field
     * @param boolean $timeAware
     */
    public function map($field, $timeAware = false)
    {
        if (null !== $this->field) {
            throw new InvalidMappingException("Softdeleteable field was already mapped as {$this->field}");
        }
        $this->field = $field;
        $this->timeAware = (bool)$timeAware;
    }

    /**
     * Get softdeletable field
     *
     * @return array
     */
    public function getField()
    {
        return $this->field;
    }

    public function isTimeAware()
    {
        return $this->timeAware;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        if (!$meta->hasField($this->field)) {
            throw new InvalidMappingException("Unable to find [{$this->field}] as mapped property in class - {$meta->name}");
        }
        $mapping = $meta->getFieldMapping($this->field);
        if (!in_array($mapping['type'], $this->validFieldTypes)) {
            $valid = implode(', ', $this->validFieldTypes);
            throw new InvalidMappingException("Cannot use field - [{$this->field}] as softdeleteable, type is not valid and must be on of: {$valid} in class - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return null === $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array('field' => $this->field);
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->field = $data['field'];
    }
}

<?php

namespace Fixture\EncoderExtension\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

final class EncoderExtensionMetadata implements ExtensionMetadataInterface
{
    /**
     * List of encoder fields and their options
     * in pairs - $encodeField => $options
     *
     * @var array
     */
    private $encoders = array();

    /**
     * Map a encode field
     *
     * @param string $field - encode field
     * @param array $options
     */
    public function mapEncoderField($field, array $options)
    {
        $this->encoders[$field] = $options;
    }

    /**
     * Get all available encode field names
     *
     * @return array
     */
    public function getEncoderFields()
    {
        return array_keys($this->encoders);
    }

    /**
     * Get mapping options for encode $field
     *
     * @param string $field
     * @return array - list of options
     */
    public function getEncoderOptions($field)
    {
        return isset($this->encoders[$field]) ? $this->encoders[$field] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->encoders as $field => $options) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find slug [{$field}] as mapped property in class - {$meta->name}");
            }
            // allow encoding only strings
            if (!in_array($options['type'], array('sha1', 'md5'))) {
                throw new InvalidMappingException("Invalid encoding type supplied, sha1 or md5 is available");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->encoders) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->encoders;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->encoders = $data;
    }
}

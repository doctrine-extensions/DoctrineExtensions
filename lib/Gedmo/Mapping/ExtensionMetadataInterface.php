<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

interface ExtensionMetadataInterface
{
    /**
     * Validates extension metadata for $meta mappings
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @throws \Gedmo\Exception\InvalidMappingException - in case if there is extension metadata validation error
     */
    function validate(ObjectManager $om, ClassMetadata $meta);

    /**
     * Checks if there is any extended metadata available
     *
     * @return boolean
     */
    function isEmpty();

    /**
     * Converts extensiom metadata to an array
     *
     * @return array
     */
    function toArray();

    /**
     * Reconstructs metadata from an array
     *
     * @param array $data
     */
    function fromArray(array $data);
}

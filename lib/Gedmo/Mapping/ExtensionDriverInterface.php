<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * Defines an interface for extension metadata driver
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface ExtensionDriverInterface
{
    /**
     * Read extended metadata configuration for
     * a single mapped class
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param \Gedmo\Mapping\ExtensionMetadataInterface $exm
     */
    function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm);

    /**
     * Passes in the mapping read by original driver
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver $driver
     */
    function setOriginalDriver(MappingDriver $driver);
}

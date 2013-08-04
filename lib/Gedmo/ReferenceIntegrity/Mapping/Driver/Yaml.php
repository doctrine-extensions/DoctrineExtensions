<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for ReferenceIntegrity
 * extension. Used for extraction of extended
 * metadata from yaml specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends FileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $mapping = $this->getMapping($meta->name);
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $property => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['referenceIntegrity'])) {
                    $exm->map($property, $fieldMapping['gedmo']['referenceIntegrity']);
                }
            }
        }
    }
}

<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
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
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']) && array_key_exists('sortable', $fieldMapping['gedmo'])) {
                    $groups = array();
                    if (isset($fieldMapping['gedmo']['sortable']['groups'])) {
                        $groups = $fieldMapping['gedmo']['sortable']['groups'];
                    }
                    $exm->map($field, array(
                        'groups' => $groups,
                        'rootClass' => $meta->isMappedSuperclass ? null : $meta->name,
                    ));
                }
            }
        }
    }
}

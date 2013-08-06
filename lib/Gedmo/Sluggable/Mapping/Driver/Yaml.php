<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
                if (isset($fieldMapping['gedmo'])) {
                    if (isset($fieldMapping['gedmo']['slug'])) {
                        $slug = $fieldMapping['gedmo']['slug'];
                        $exm->map($field, array(
                            'fields' => $slug['fields'],
                            'style' => isset($slug['style']) ? (string)$slug['style'] : 'default',
                            'updatable' => isset($slug['updatable']) ? (bool)$slug['updatable'] : true,
                            'unique' => isset($slug['unique']) ? (bool)$slug['unique'] : true,
                            'unique_base' => isset($slug['unique_base']) ? $slug['unique_base'] : null,
                            'separator' => isset($slug['separator']) ? (string)$slug['separator'] : '-',
                            'prefix' => isset($slug['prefix']) ? (string)$slug['prefix'] : '',
                            'suffix' => isset($slug['suffix']) ? (string)$slug['suffix'] : '',
                            'rootClass' => $meta->isMappedSuperclass ? null : $meta->name,
                        ));
                    }
                }
            }
        }
    }
}

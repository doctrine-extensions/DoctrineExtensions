<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
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
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $data = $fieldMapping['gedmo']['blameable'];
                    $options = array('on' => 'update');
                    if (isset($data['on'])) {
                        $options['on'] = strtolower($data['on']);
                    }
                    if ($options['on'] === 'change') {
                        if (isset($data['field'])) {
                            $options['field'] = $data['field'];
                        }
                        $options['value'] = isset($data['value']) ? $data['value'] : null;
                    }
                    $exm->map($field, $options);
                }
            }
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $data = $fieldMapping['gedmo']['blameable'];
                    $options = array('on' => 'update');
                    if (isset($data['on'])) {
                        $options['on'] = strtolower($data['on']);
                    }
                    if ($options['on'] === 'change') {
                        if (isset($data['field'])) {
                            $options['field'] = $data['field'];
                        }
                        $options['value'] = isset($data['value']) ? $data['value'] : null;
                    }
                    $exm->map($field, $options);
                }
            }
        }
    }
}

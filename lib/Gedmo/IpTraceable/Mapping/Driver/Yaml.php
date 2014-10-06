<?php

namespace Gedmo\IpTraceable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is a yaml mapping driver for IpTraceable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for IpTraceable
 * extension.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
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
                if (isset($fieldMapping['gedmo']['ipTraceable'])) {
                    $data = $fieldMapping['gedmo']['ipTraceable'];
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

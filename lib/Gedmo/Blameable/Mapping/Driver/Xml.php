<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a xml mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        /**
         * @var \SimpleXmlElement $mapping
         */
        $mapping = $this->getMapping($meta->name);
        if (isset($mapping->field)) {
            /**
             * @var \SimpleXmlElement $fieldMapping
             */
            foreach ($mapping->field as $fieldMapping) {
                $fieldMappingDoctrine = $fieldMapping;
                $fieldMapping = $fieldMapping->children(self::GEDMO_NAMESPACE_URI);
                if (isset($fieldMapping->blameable)) {
                    /**
                     * @var \SimpleXmlElement $data
                     */
                    $data = $fieldMapping->blameable;

                    $field = $this->getAttribute($fieldMappingDoctrine, 'name');
                    $options = array('on' => 'update');
                    if ($this->isAttributeSet($data, 'on')) {
                        $options['on'] = strtolower($this->getAttribute($data, 'on'));
                    }
                    if ($options['on'] === 'change') {
                        if ($this->isAttributeSet($data, 'field')) {
                            $options['field'] = array_map('trim', explode(",", $this->getAttribute($data, 'field')));
                            if (count($options['field']) === 1) {
                                $options['field'] = current($options['field']);
                            }
                        }
                        $options['value'] = $this->isAttributeSet($data, 'value') ? $this->getAttribute($data, 'value' ) : null;
                    }
                    $exm->map($field, $options);
                }
            }
        }

        if (isset($mapping->{'many-to-one'})) {
            foreach ($mapping->{'many-to-one'} as $fieldMapping) {
                $field = $this->getAttribute($fieldMapping, 'field');
                $fieldMapping = $fieldMapping->children(self::GEDMO_NAMESPACE_URI);
                if (isset($fieldMapping->blameable)) {
                    $data = $fieldMapping->blameable;
                    $field = $this->getAttribute($fieldMappingDoctrine, 'name');
                    $options = array('on' => 'update');
                    if ($this->isAttributeSet($data, 'on')) {
                        $options['on'] = strtolower($this->getAttribute($data, 'on'));
                    }
                    if ($options['on'] === 'change') {
                        if ($this->isAttributeSet($data, 'field')) {
                            $options['field'] = array_map('trim', explode(",", $this->getAttribute($data, 'field')));
                            if (count($options['field']) === 1) {
                                $options['field'] = current($options['field']);
                            }
                        }
                        $options['value'] = $this->isAttributeSet($data, 'value') ? $this->getAttribute($data, 'value' ) : null;
                    }
                    $exm->map($field, $options);
                }
            }
        }
    }
}

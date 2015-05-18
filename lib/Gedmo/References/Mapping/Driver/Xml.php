<?php
/**
 * Xml.php
 *
 * @author dbojdo - Daniel Bojdo <daniel.bojdo@dxi.eu>
 * Created on May 18, 2015, 13:38
 * Copyright (C) DXI Ltd
 */

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * Class Xml
 * @package Gedmo\References\Mapping\Driver
 */
class Xml extends BaseXml
{
    private static $referenceTypes = array(
        'referenceOne' => 'reference-one',
        'referenceMany' => 'reference-many',
        'referenceManyEmbed' => 'reference-many-embed'
    );

    /**
     * Read extended metadata configuration for
     * a single mapped class
     *
     * @param object $meta
     * @param array $config
     *
     * @return void
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $mapping
         */
        $mapping = $this->_getMapping($meta->name);
        $gedmoMapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

        foreach (self::$referenceTypes as $type => $tagName) {
            $config[$type] = array();

            if (isset($gedmoMapping->{$tagName})) {
                foreach ($gedmoMapping->{$tagName} as $referenceConfig) {
                    $config[$type][] = $this->createReferenceConfig($tagName, $referenceConfig);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param \SimpleXMLElement $referenceConfig
     * @return array
     */
    private function createReferenceConfig($type, \SimpleXMLElement $referenceConfig)
    {
        $config = array();
        $config['field'] = $this->_getAttribute($referenceConfig, 'field');
        $config['type'] = $this->_getAttribute($referenceConfig, 'type');
        $config['class'] =  $this->_getAttribute($referenceConfig, 'class');
        $config['identifier'] =  $this->_getAttribute($referenceConfig, 'identifier');
        $config['inversedBy'] =  $this->_getAttribute($referenceConfig, 'inversed-by');
        $config['mappedBy'] =  $this->_getAttribute($referenceConfig, 'mapped-by');

        return $config;
    }
}

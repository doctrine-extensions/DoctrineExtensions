<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'mapped-superclass')) {
            if (isset($xml->{'translation-class'})) {
                /**
                 * @var \SimpleXmlElement $data
                 */
                $data = $xml->{'translation-class'};
                if ($this->_isAttributeSet($data, 'name')) {
                    if (!class_exists($name = $this->_getAttribute($data, 'name'))) {
                        $ns = substr($meta->name, 0, strrpos($meta->name, '\\'));
                        if (!class_exists($name = $ns.'\\'.$name)) {
                            throw new InvalidMappingException("Translation class: ".$this->_getAttribute($data, 'name')." does not exist."
                                . " If you haven't generated it yet, use TranslatableCommand to do so");
                        }
                    }
                    $config['translationClass'] = $name;
                }
            }
        }

        if (isset($xmlDoctrine->field)) {
            foreach ($xmlDoctrine->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);
                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->translatable)) {
                    $config['fields'][$field] = array();
                }
            }
        }
    }
}

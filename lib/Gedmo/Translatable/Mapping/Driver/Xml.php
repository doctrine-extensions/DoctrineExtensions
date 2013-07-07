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
                            $config['translationClass'] = $this->_getAttribute($data, 'name');
                            throw new InvalidMappingException("Translation class: ".$this->_getAttribute($data, 'name')." does not exist."
                                . " If you haven't generated it yet, use TranslatableCommand to do so", $config);
                        }
                    }
                    $config['translationClass'] = $name;
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // try to guess translation class
            ($parts = explode('\\', $meta->name)) && ($name = array_pop($parts));
            if (class_exists($fullname = implode('\\', $parts).'\\'.$name.'Translation')) {
                $config['translationClass'] = $fullname;
            } elseif (class_exists($fullname2 = implode('\\', $parts).'\\Translation\\'.$name)) {
                $config['translationClass'] = $fullname2;
            } else {
                throw new InvalidMappingException("Tried to guess translation class as {$fullname} or {$fullname2}"
                    . ", but could not locate it. If you haven't generated it yet, use TranslatableCommand to do so"
                    . ", if it is available elsewhere, specify it in configuration with 'translationClass'", $config);
            }
        }
    }
}

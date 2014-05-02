<?php

namespace Gedmo\SoftDeletable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\SoftDeletable\Mapping\Validator;

/**
 * This is a xml mapping driver for SoftDeletable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for SoftDeletable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (in_array($xmlDoctrine->getName(), array('mapped-superclass', 'entity', 'document', 'embedded-document'))) {
            if (isset($xml->{'soft-deletable'})) {
                $field = $this->_getAttribute($xml->{'soft-deletable'}, 'field-name');

                if (!$field) {
                    throw new InvalidMappingException('Field name for SoftDeletable class is mandatory.');
                }

                Validator::validateField($meta, $field);

                $config['softDeletable'] = true;
                $config['fieldName'] = $field;

                $config['timeAware'] = false;
                if($this->_isAttributeSet($xml->{'soft-deletable'}, 'time-aware')) {
                    $config['timeAware'] = $this->_getBooleanAttribute($xml->{'soft-deletable'}, 'time-aware');
                }
            }
        }
    }
}

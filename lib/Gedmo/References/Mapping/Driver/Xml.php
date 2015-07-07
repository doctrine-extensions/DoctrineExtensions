<?php

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for References
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for References
 * extension.
 *
 * @author Aram Alipoor <aram.alipoor@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * @var array
     */
    private $validTypes = array(
        'document',
        'entity'
    );

    /**
     * @var array
     */
    private $validReferences = array(
        'referenceOne',
        'referenceMany',
        'referenceManyEmbed'
    );

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

        if ($xmlDoctrine->getName() === 'entity' || $xmlDoctrine->getName() === 'document' || $xmlDoctrine->getName() === 'mapped-superclass') {
            if (isset($xml->reference)) {
                /**
                 * @var \SimpleXMLElement $element
                 */
                foreach ($xml->reference as $element) {
                    if (!$this->_isAttributeSet($element, 'type')) {
                        throw new InvalidMappingException("Reference type (document or entity) is not set in class - {$meta->name}");
                    }

                    $type = $this->_getAttribute($element, 'type');
                    if (!in_array($type, $this->validTypes)) {
                        throw new InvalidMappingException(
                            $type .
                            ' is not a valid reference type, valid types are: ' .
                            implode(', ', $this->validTypes)
                        );
                    }

                    $reference = $this->_getAttribute($element, 'reference');
                    if (!in_array($reference, $this->validReferences)) {
                        throw new InvalidMappingException(
                            $reference .
                            ' is not a valid reference, valid references are: ' .
                            implode(', ', $this->validReferences)
                        );
                    }

                    if (!$this->_isAttributeSet($element, 'field')) {
                        throw new InvalidMappingException("Reference field is not set in class - {$meta->name}");
                    }
                    $field = $this->_getAttribute($element, 'field');

                    if (!$this->_isAttributeSet($element, 'class')) {
                        throw new InvalidMappingException("Reference field is not set in class - {$meta->name}");
                    }
                    $class = $this->_getAttribute($element, 'class');

                    if (!$this->_isAttributeSet($element, 'identifier')) {
                        throw new InvalidMappingException("Reference identifier is not set in class - {$meta->name}");
                    }
                    $identifier = $this->_getAttribute($element, 'identifier');

                    $config[$reference][$field] = array(
                        'field' => $field,
                        'type' => $type,
                        'class' => $class,
                        'identifier' => $identifier
                    );

                    if (!$this->_isAttributeSet($element, 'mappedBy')) {
                        $config[$reference][$field]['mappedBy'] = $this->_getAttribute($element, 'mappedBy');
                    }
                    if (!$this->_isAttributeSet($element, 'inversedBy')) {
                        $config[$reference][$field]['inversedBy'] = $this->_getAttribute($element, 'inversedBy');
                    }
                }
            }
        }
    }
}

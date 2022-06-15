<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for References
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for References
 * extension.
 *
 * @author Aram Alipoor <aram.alipoor@gmail.com>
 */
class Xml extends BaseXml
{
    /**
     * @var array
     */
    private $validTypes = [
        'document',
        'entity',
    ];

    /**
     * @var array
     */
    private $validReferences = [
        'referenceOne',
        'referenceMany',
        'referenceManyEmbed',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->getName());
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if ('entity' === $xmlDoctrine->getName() || 'document' === $xmlDoctrine->getName() || 'mapped-superclass' === $xmlDoctrine->getName()) {
            if (isset($xml->reference)) {
                /**
                 * @var \SimpleXMLElement
                 */
                foreach ($xml->reference as $element) {
                    if (!$this->_isAttributeSet($element, 'type')) {
                        throw new InvalidMappingException("Reference type (document or entity) is not set in class - {$meta->getName()}");
                    }

                    $type = $this->_getAttribute($element, 'type');
                    if (!in_array($type, $this->validTypes, true)) {
                        throw new InvalidMappingException($type.' is not a valid reference type, valid types are: '.implode(', ', $this->validTypes));
                    }

                    $reference = $this->_getAttribute($element, 'reference');
                    if (!in_array($reference, $this->validReferences, true)) {
                        throw new InvalidMappingException($reference.' is not a valid reference, valid references are: '.implode(', ', $this->validReferences));
                    }

                    if (!$this->_isAttributeSet($element, 'field')) {
                        throw new InvalidMappingException("Reference field is not set in class - {$meta->getName()}");
                    }
                    $field = $this->_getAttribute($element, 'field');

                    if (!$this->_isAttributeSet($element, 'class')) {
                        throw new InvalidMappingException("Reference field is not set in class - {$meta->getName()}");
                    }
                    $class = $this->_getAttribute($element, 'class');

                    if (!$this->_isAttributeSet($element, 'identifier')) {
                        throw new InvalidMappingException("Reference identifier is not set in class - {$meta->getName()}");
                    }
                    $identifier = $this->_getAttribute($element, 'identifier');

                    $config[$reference][$field] = [
                        'field' => $field,
                        'type' => $type,
                        'class' => $class,
                        'identifier' => $identifier,
                    ];

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

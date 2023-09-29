<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;
use Gedmo\SoftDeleteable\Mapping\Validator;

/**
 * This is a xml mapping driver for SoftDeleteable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for SoftDeleteable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 *
 * @internal
 */
class Xml extends BaseXml
{
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->getName());
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (in_array($xmlDoctrine->getName(), ['mapped-superclass', 'entity', 'document', 'embedded-document'], true)) {
            if (isset($xml->{'soft-deleteable'})) {
                $field = $this->_getAttribute($xml->{'soft-deleteable'}, 'field-name');

                if (!$field) {
                    throw new InvalidMappingException('Field name for SoftDeleteable class is mandatory.');
                }

                Validator::validateField($meta, $field);

                $config['softDeleteable'] = true;
                $config['fieldName'] = $field;

                $config['timeAware'] = false;
                if ($this->_isAttributeSet($xml->{'soft-deleteable'}, 'time-aware')) {
                    $config['timeAware'] = $this->_getBooleanAttribute($xml->{'soft-deleteable'}, 'time-aware');
                }

                $config['hardDelete'] = true;
                if ($this->_isAttributeSet($xml->{'soft-deleteable'}, 'hard-delete')) {
                    $config['hardDelete'] = $this->_getBooleanAttribute($xml->{'soft-deleteable'}, 'hard-delete');
                }
            }
        }

        return $config;
    }
}

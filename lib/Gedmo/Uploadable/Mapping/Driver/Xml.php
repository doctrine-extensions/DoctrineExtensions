<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\Uploadable\Mapping\Validator;

/**
 * This is a xml mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @package Gedmo.Uploadable.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
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

        if ($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'mapped-superclass') {
            if (isset($xml->uploadable)) {
                $xmlUploadable = $xml->uploadable;
                $config['uploadable'] = true;
                $config['allowOverwrite'] = $this->_isAttributeSet($xmlUploadable, 'allow-overwrite') ?
                    (bool) $this->_getAttribute($xmlUploadable, 'allow-overwrite') : false;
                $config['appendNumber'] = $this->_isAttributeSet($xmlUploadable, 'append-number') ?
                    (bool) $this->_getAttribute($xmlUploadable, 'append-number') : false;
                $config['path'] = $this->_getAttribute($xml->{'uploadable'}, 'path');
                $config['pathMethod'] = $this->_getAttribute($xml->{'uploadable'}, 'path-method');
                $config['fileInfoProperty'] = $this->_getAttribute($xml->{'uploadable'}, 'file-info-property');
                $config['fileMimeTypeField'] = false;
                $config['filePathField'] = false;
                $config['fileSizeField'] = false;

                if (isset($xmlDoctrine->field)) {
                    foreach ($xmlDoctrine->field as $mapping) {
                        $mappingDoctrine = $mapping;
                        $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                        $field = $this->_getAttribute($mappingDoctrine, 'name');

                        if (isset($mapping->{'uploadable-file-mime-type'})) {
                            $config['fileMimeTypeField'] = $field;
                        } else if (isset($mapping->{'uploadable-file-size'})) {
                            $config['fileSizeField'] = $field;
                        } else if (isset($mapping->{'uploadable-file-path'})) {
                            $config['filePathField'] = $field;
                        }
                    }
                }

                Validator::validateConfiguration($meta, $config);
            }
        }
    }
}

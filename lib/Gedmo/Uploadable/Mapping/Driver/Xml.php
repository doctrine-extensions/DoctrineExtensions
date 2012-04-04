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
            // TODO

            /*
             if (isset($xml->uploadable)) {
                $config['uploadable'] = true;
                $config['allowOverwrite'] = isset($xml->allowOverwrite) ? $xml->allowOverwrite : false;
                $config['appendNumber'] = isset($xml->appendNumber) ? $xml->appendNumber: false;
                $config['path'] = isset($xml->path) ? $xml->path: '';
                $config['pathMethod'] = '';
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

                            Validator::validateFileMimeTypeField($meta, $config['fileMimeTypeField']);
                        } else if (isset($mapping->{'uploadable-file-size'})) {
                            $config['fileSizeField'] = $field;

                            Validator::validateFileSizeField($meta, $config['fileSizeField']);
                        } else if (isset($mapping->{'uploadable-file-path'})) {
                            $config['filePathField'] = $field;

                            Validator::validateFilePathField($meta, $config['filePathField']);
                        } else if (isset($mapping->{'uploadable-file-info'})) {
                            $config['fileInfoField'] = $field;
                        }
                    }
                }

                Validator::validateConfiguration($meta, $config);
            }
            */
        }
    }
}

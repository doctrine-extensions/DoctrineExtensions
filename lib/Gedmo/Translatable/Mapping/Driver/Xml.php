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

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // try to guess translation class
            $config['translationClass'] = $meta->name . 'Translation';
            if (!class_exists($config['translationClass'])) {
                throw new InvalidMappingException("Translation class {$config['translationClass']} for domain object {$meta->name}"
                    . ", was not found or could not be autoloaded. If it was not generated yet, use GenerateTranslationsCommand", $config);
            }
        }
    }
}

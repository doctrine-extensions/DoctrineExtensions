<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Uploadable\Mapping\UploadableMetadata;

/**
 * This is a xml mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $xml = $this->getMapping($meta->name);
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if ($xmlDoctrine->getName() == 'entity' || $xmlDoctrine->getName() == 'document' || $xmlDoctrine->getName() == 'mapped-superclass') {
            if (isset($xml->uploadable)) {
                $xmlUploadable = $xml->uploadable;
                $options['allowOverwrite'] = $this->isAttributeSet($xmlUploadable, 'allow-overwrite') ?
                    (bool) $this->getAttribute($xmlUploadable, 'allow-overwrite') : false;
                $options['appendNumber'] = $this->isAttributeSet($xmlUploadable, 'append-number') ?
                    (bool) $this->getAttribute($xmlUploadable, 'append-number') : false;
                $options['path'] = $this->isAttributeSet($xmlUploadable, 'path') ?
                    $this->getAttribute($xml->{'uploadable'}, 'path') : '';
                $options['pathMethod'] = $this->isAttributeSet($xmlUploadable, 'path-method') ?
                    $this->getAttribute($xml->{'uploadable'}, 'path-method') : '';
                $options['callback'] = $this->isAttributeSet($xmlUploadable, 'callback') ?
                    $this->getAttribute($xml->{'uploadable'}, 'callback') : '';
                $options['fileMimeTypeField'] = false;
                $options['filePathField'] = false;
                $options['fileSizeField'] = false;
                $options['filenameGenerator'] = $this->isAttributeSet($xmlUploadable, 'filename-generator') ?
                    $this->getAttribute($xml->{'uploadable'}, 'filename-generator') :
                    UploadableMetadata::GENERATOR_NONE;
                $options['maxSize'] = $this->isAttributeSet($xmlUploadable, 'max-size') ?
                    (double) $this->getAttribute($xml->{'uploadable'}, 'max-size') :
                    (double) 0;
                $options['allowedTypes'] = $this->isAttributeSet($xmlUploadable, 'allowed-types') ?
                    $this->getAttribute($xml->{'uploadable'}, 'allowed-types') :
                    '';
                $options['disallowedTypes'] = $this->isAttributeSet($xmlUploadable, 'disallowed-types') ?
                    $this->getAttribute($xml->{'uploadable'}, 'disallowed-types') :
                    '';

                if (isset($xmlDoctrine->field)) {
                    foreach ($xmlDoctrine->field as $mapping) {
                        $mappingDoctrine = $mapping;
                        $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                        $field = $this->getAttribute($mappingDoctrine, 'name');

                        if (isset($mapping->{'uploadable-file-mime-type'})) {
                            $options['fileMimeTypeField'] = $field;
                        } else if (isset($mapping->{'uploadable-file-size'})) {
                            $options['fileSizeField'] = $field;
                        } else if (isset($mapping->{'uploadable-file-path'})) {
                            $options['filePathField'] = $field;
                        }
                    }
                }
                $exm->map($options);
            }
        }
    }
}

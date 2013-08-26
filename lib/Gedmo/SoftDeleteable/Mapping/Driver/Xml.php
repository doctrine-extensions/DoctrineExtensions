<?php

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for SoftDeleteable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for SoftDeleteable
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

        if (in_array($xmlDoctrine->getName(), array('mapped-superclass', 'entity', 'document', 'embedded-document'))) {
            if (isset($xml->{'soft-deleteable'})) {
                $data = $xml->{'soft-deleteable'};
                if (!$field = $this->getAttribute($data, 'field-name')) {
                    throw new InvalidMappingException("Field name for SoftDeleteable class is mandatory in class {$meta->name}.");
                }
                $timeAware = false;
                if ($this->isAttributeSet($data, 'time-aware')) {
                    $timeAware = $this->getBooleanAttribute($data, 'time-aware');
                }
                $exm->map($field, $timeAware);
            }
        }
    }
}

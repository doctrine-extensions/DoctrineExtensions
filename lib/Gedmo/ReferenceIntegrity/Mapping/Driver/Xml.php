<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is a xml mapping driver for ReferenceIntegrity
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Timestampable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        /** @var \SimpleXMLElement $xml */
        $xml = $this->getMapping($meta->name);
        if (isset($xml->field)) {
            foreach ($xml->field as $fieldMappingDoctrine) {
                $fieldMapping = $fieldMappingDoctrine->children(self::GEDMO_NAMESPACE_URI);
                if (isset($fieldMapping->{'reference-integrity'})) {
                    $data = $fieldMapping->{'reference-integrity'};

                    $field = $this->getAttribute($fieldMappingDoctrine, 'name');
                    $action = null;
                    if ($this->isAttributeSet($data, 'action')) {
                        $action = strtolower($this->getAttribute($data, 'action'));
                    }
                    $exm->map($field, $action);
                }
            }
        }
    }
}

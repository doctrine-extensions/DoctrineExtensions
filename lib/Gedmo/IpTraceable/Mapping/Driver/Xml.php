<?php

namespace Gedmo\IpTraceable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is a xml mapping driver for IpTraceable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for IpTraceable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends XmlFileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        /** @var \SimpleXMLElement $mapping */
        $mapping = $this->getMapping($meta->name);
        if (isset($mapping->field)) {
            foreach ($mapping->field as $fieldMappingDoctrine) {
                $fieldMapping = $fieldMappingDoctrine->children(self::GEDMO_NAMESPACE_URI);
                if (isset($fieldMapping->{'ip-traceable'})) {
                    /** @var \SimpleXMLElement $data */
                    $data = $fieldMapping->{'ip-traceable'};

                    $field = $this->getAttribute($fieldMappingDoctrine, 'name');
                    $options = array('on' => 'update');
                    if ($this->isAttributeSet($data, 'on')) {
                        $options['on'] = strtolower($this->getAttribute($data, 'on'));
                    }
                    if ($options['on'] === 'change') {
                        if ($this->isAttributeSet($data, 'field')) {
                            $options['field'] = array_map('trim', explode(',', $this->getAttribute($data, 'field')));
                            if (count($options['field']) === 1) {
                                $options['field'] = current($options['field']);
                            }
                        }
                        $options['value'] = $this->isAttributeSet($data, 'value') ? $this->getAttribute($data, 'value' ) : null;
                    }
                    $exm->map($field, $options);
                }
            }
        }
    }
}

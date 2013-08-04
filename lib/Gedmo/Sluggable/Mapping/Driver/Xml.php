<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sluggable
 * extension.
 *
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
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->getMapping($meta->name);
        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->slug)) {
                    /**
                     * @var \SimpleXmlElement $slug
                     */
                    $slug = $mapping->slug;
                    $fields = array_map('trim', explode(',', (string)$this->_getAttribute($slug, 'fields')));
                    $exm->mapSlugField($field, array(
                        'fields' => $fields,
                        'style' => $this->isAttributeSet($slug, 'style') ?
                            $this->getAttribute($slug, 'style') : 'default',
                        'updatable' => $this->isAttributeSet($slug, 'updatable') ?
                            $this->getBooleanAttribute($slug, 'updatable') : true,
                        'unique' => $this->isAttributeSet($slug, 'unique') ?
                            $this->getBooleanAttribute($slug, 'unique') : true,
                        'unique_base' => $this->isAttributeSet($slug, 'unique_base') ?
                            $this->getAttribute($slug, 'unique_base') : null,
                        'separator' => $this->isAttributeSet($slug, 'separator') ?
                            $this->getAttribute($slug, 'separator') : '-',
                        'prefix' => $this->isAttributeSet($slug, 'prefix') ?
                            $this->getAttribute($slug, 'prefix') : '',
                        'suffix' => $this->isAttributeSet($slug, 'suffix') ?
                            $this->getAttribute($slug, 'suffix') : '',
                        'rootClass' => $meta->isMappedSuperclass ? null : $meta->name,
                    ));
                }
            }
        }
    }
}

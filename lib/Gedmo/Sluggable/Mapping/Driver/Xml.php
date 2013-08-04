<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\XmlFileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Sluggable
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
                    $exm->map($field, array(
                        'fields' => $fields,
<<<<<<< HEAD
                        'slug' => $field,
                        'style' => $this->_isAttributeSet($slug, 'style') ?
                            $this->_getAttribute($slug, 'style') : 'default',
                        'updatable' => $this->_isAttributeSet($slug, 'updatable') ?
                            $this->_getBooleanAttribute($slug, 'updatable') : true,
                        'dateFormat' => $this->_isAttributeSet($slug, 'dateFormat') ?
                            $this->_getAttribute($slug, 'dateFormat') : 'Y-m-d-H:i',
                        'unique' => $this->_isAttributeSet($slug, 'unique') ?
                            $this->_getBooleanAttribute($slug, 'unique') : true,
                        'unique_base' => $this->_isAttributeSet($slug, 'unique_base') ?
                            $this->_getAttribute($slug, 'unique_base') : null,
                        'separator' => $this->_isAttributeSet($slug, 'separator') ?
                            $this->_getAttribute($slug, 'separator') : '-',
                        'prefix' => $this->_isAttributeSet($slug, 'prefix') ?
                            $this->_getAttribute($slug, 'prefix') : '',
                        'suffix' => $this->_isAttributeSet($slug, 'suffix') ?
                            $this->_getAttribute($slug, 'suffix') : '',
                    );
                    if (!$meta->isMappedSuperclass && $meta->isIdentifier($field) && !$config['slugs'][$field]['unique']) {
                        throw new InvalidMappingException("Identifier field - [{$field}] slug must be unique in order to maintain primary key in class - {$meta->name}");
                    }
                    $ubase = $config[$field]['unique_base'];
                    if ($config[$field]['unique'] === false && $ubase) {
                        throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
                    }
                    if ($ubase && !$this->isValidField($meta, $ubase) && !$meta->hasAssociation($ubase)) {
                        throw new InvalidMappingException("Unable to find [{$ubase}] as mapped property in entity - {$meta->name}");
                    }
=======
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
>>>>>>> [mapping] start to refactor mapping extension
                }
            }
        }
    }
}

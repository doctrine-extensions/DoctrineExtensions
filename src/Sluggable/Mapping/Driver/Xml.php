<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 *
 * @internal
 */
class Xml extends BaseXml
{
    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'string',
        'text',
        'integer',
        'int',
        'datetime',
        'citext',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->getName());

        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $field = $this->_getAttribute($mapping, 'name');
                $config = $this->buildFieldConfiguration($meta, $field, $mapping, $config);
            }
        }

        if (isset($xml->{'attribute-overrides'})) {
            foreach ($xml->{'attribute-overrides'}->{'attribute-override'} as $mapping) {
                $field = $this->_getAttribute($mapping, 'name');
                $config = $this->buildFieldConfiguration($meta, $field, $mapping->field, $config);
            }
        }

        return $config;
    }

    /**
     * Checks if $field type is valid as Sluggable field
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping->type ?? $mapping['type'], self::VALID_TYPES, true);
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param array<string, mixed>  $config
     *
     * @return array<string, mixed>
     */
    private function buildFieldConfiguration(ClassMetadata $meta, string $field, \SimpleXMLElement $mapping, array $config): array
    {
        /**
         * @var \SimpleXmlElement
         */
        $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

        if (isset($mapping->slug)) {
            /**
             * @var \SimpleXmlElement
             */
            $slug = $mapping->slug;
            if (!$this->isValidField($meta, $field)) {
                throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->getName()}");
            }
            $fields = array_map('trim', explode(',', (string) $this->_getAttribute($slug, 'fields')));
            foreach ($fields as $slugField) {
                if (!$meta->hasField($slugField)) {
                    throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$this->isValidField($meta, $slugField)) {
                    throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->getName()}");
                }
            }

            $handlers = [];
            if (isset($slug->handler)) {
                foreach ($slug->handler as $handler) {
                    $class = (string) $this->_getAttribute($handler, 'class');
                    $handlers[$class] = [];
                    foreach ($handler->{'handler-option'} as $option) {
                        $handlers[$class][(string) $this->_getAttribute($option, 'name')]
                            = (string) $this->_getAttribute($option, 'value')
                        ;
                    }
                    $class::validate($handlers[$class], $meta);
                }
            }

            // set all options
            $config['slugs'][$field] = [
                'fields' => $fields,
                'slug' => $field,
                'style' => $this->_isAttributeSet($slug, 'style') ?
                    $this->_getAttribute($slug, 'style') : 'default',
                'updatable' => $this->_isAttributeSet($slug, 'updatable') ?
                    $this->_getBooleanAttribute($slug, 'updatable') : true,
                'dateFormat' => $this->_isAttributeSet($slug, 'dateFormat') ?
                    $this->_getAttribute($slug, 'dateFormat') : 'Y-m-d-H:i',
                'unique' => $this->_isAttributeSet($slug, 'unique') ?
                    $this->_getBooleanAttribute($slug, 'unique') : true,
                'unique_base' => $this->_isAttributeSet($slug, 'unique-base') ?
                    $this->_getAttribute($slug, 'unique-base') : null,
                'separator' => $this->_isAttributeSet($slug, 'separator') ?
                    $this->_getAttribute($slug, 'separator') : '-',
                'prefix' => $this->_isAttributeSet($slug, 'prefix') ?
                    $this->_getAttribute($slug, 'prefix') : '',
                'suffix' => $this->_isAttributeSet($slug, 'suffix') ?
                    $this->_getAttribute($slug, 'suffix') : '',
                'handlers' => $handlers,
                'uniqueOverTranslations' => $this->_isAttributeSet($slug, 'uniqueOverTranslations') ?
                    $this->_getBooleanAttribute($slug, 'uniqueOverTranslations') : false,
            ];
            if (!$meta->isMappedSuperclass && $meta->isIdentifier($field) && !$config['slugs'][$field]['unique']) {
                throw new InvalidMappingException("Identifier field - [{$field}] slug must be unique in order to maintain primary key in class - {$meta->getName()}");
            }
            $ubase = $config['slugs'][$field]['unique_base'];
            if (false === $config['slugs'][$field]['unique'] && $ubase) {
                throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
            }
            if ($ubase && !$meta->hasField($ubase) && !$meta->hasAssociation($ubase)) {
                throw new InvalidMappingException("Unable to find [{$ubase}] as mapped property in entity - {$meta->getName()}");
            }
        }

        return $config;
    }
}

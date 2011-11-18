<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validTypes = array(
        'string',
        'text',
        'integer'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {}

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);

        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $mappingDoctrine = $mapping;
                /**
                 * @var \SimpleXmlElement $mapping
                 */
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->slug)) {
                    /**
                     * @var \SimpleXmlElement $slug
                     */
                    $slug = $mapping->slug;
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                    }
                    $fields = array_map('trim', explode(',', (string)$this->_getAttribute($slug, 'fields')));
                    foreach ($fields as $slugField) {
                        if (!$meta->hasField($slugField)) {
                            throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->name}");
                        }
                        if (!$this->isValidField($meta, $slugField)) {
                            throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                        }
                    }

                    $handlers = array();
                    if (isset($slug->handler)) {
                        foreach ($slug->handler as $handler) {
                            $class = (string)$this->_getAttribute($handler, 'class');
                            $handlers[$class] = array();
                            foreach ($handler->{'handler-option'} as $option) {
                                $handlers[$class][(string)$this->_getAttribute($option, 'name')]
                                    = (string)$this->_getAttribute($option, 'value')
                                ;
                            }
                            $class::validate($handlers[$class], $meta);
                        }
                    }
                    // set all options
                    $config['slugs'][$field] = array(
                        'fields' => $fields,
                        'slug' => $field,
                        'style' => $this->_isAttributeSet($slug, 'style') ?
                            $this->_getAttribute($slug, 'style') : 'default',
                        'updatable' => $this->_isAttributeSet($slug, 'updatable') ?
                            (bool)$this->_getAttribute($slug, 'updatable') : true,
                        'unique' => $this->_isAttributeSet($slug, 'unique') ?
                            (bool)$this->_getAttribute($slug, 'unique') : true,
                        'separator' => $this->_isAttributeSet($slug, 'separator') ?
                            $this->_getAttribute($slug, 'separator') : '-',
                        'handlers' => $handlers
                    );
                }
            }
        }
    }

    /**
     * Checks if $field type is valid as Sluggable field
     *
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField(ClassMetadata $meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}

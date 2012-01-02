<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Annotation\SlugHandler;
use Gedmo\Mapping\Annotation\SlugHandlerOption;
use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to identify field as one which holds the slug
     * together with slug options
     */
    const SLUG = 'Gedmo\\Mapping\\Annotation\\Slug';

    /**
     * SlugHandler extension annotation
     */
    const HANDLER = 'Gedmo\\Mapping\\Annotation\\SlugHandler';

    /**
     * SlugHandler option annotation
     */
    const HANDLER_OPTION ='Gedmo\\Mapping\\Annotation\\SlugHandlerOption';

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
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $class = $meta->getReflectionClass();
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // slug property
            if ($slug = $this->reader->getPropertyAnnotation($property, self::SLUG)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find slug [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                }
                // process slug handlers
                $handlers = array();
                if (is_array($slug->handlers) && $slug->handlers) {
                    foreach ($slug->handlers as $handler) {
                        if (!$handler instanceof SlugHandler) {
                            throw new InvalidMappingException("SlugHandler: {$handler} should be instance of SlugHandler annotation in entity - {$meta->name}");
                        }
                        if (!strlen($handler->class)) {
                            throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->name}");
                        }
                        $class = $handler->class;
                        $handlers[$class] = array();
                        foreach ((array)$handler->options as $option) {
                            if (!$option instanceof SlugHandlerOption) {
                                throw new InvalidMappingException("SlugHandlerOption: {$option} should be instance of SlugHandlerOption annotation in entity - {$meta->name}");
                            }
                            if (!strlen($option->name)) {
                                throw new InvalidMappingException("SlugHandlerOption name: {$option->name} should be valid name in entity - {$meta->name}");
                            }
                            $handlers[$class][$option->name] = $option->value;
                        }
                        $class::validate($handlers[$class], $meta);
                    }
                }
                // process slug fields
                if (empty($slug->fields) || !is_array($slug->fields)) {
                    throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->name}");
                }
                foreach ($slug->fields as $slugField) {
                    if (!$meta->hasField($slugField)) {
                        throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->name}");
                    }
                    if (!$this->isValidField($meta, $slugField)) {
                        throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                    }
                }
                if (!is_bool($slug->updatable)) {
                    throw new InvalidMappingException("Slug annotation [updatable], type is not valid and must be 'boolean' in class - {$meta->name}");
                }
                if (!is_bool($slug->unique)) {
                    throw new InvalidMappingException("Slug annotation [unique], type is not valid and must be 'boolean' in class - {$meta->name}");
                }
                // set all options
                $config['slugs'][$field] = array(
                    'fields' => $slug->fields,
                    'slug' => $field,
                    'style' => $slug->style,
                    'updatable' => $slug->updatable,
                    'unique' => $slug->unique,
                    'separator' => $slug->separator,
                    'handlers' => $handlers
                );
            }
        }
    }

    /**
     * Checks if $field type is valid as Sluggable field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}
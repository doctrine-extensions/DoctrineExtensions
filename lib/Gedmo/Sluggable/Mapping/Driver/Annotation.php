<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Annotation\SlugHandler;
use Gedmo\Mapping\Annotation\SlugHandlerOption;
use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
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
     * Annotation to mark field as sluggable and include it in slug building
     */
    const SLUGGABLE = 'Gedmo\\Mapping\\Annotation\\Sluggable';

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
        'text'
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
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config) {
            if (!isset($config['fields'])) {
                throw new InvalidMappingException("Unable to find any sluggable fields specified for Sluggable entity - {$meta->name}");
            }
            foreach ($config['fields'] as $slugField => $fields) {
                if (!isset($config['slugFields'][$slugField])) {
                    throw new InvalidMappingException("Unable to find {$slugField} slugField specified for Sluggable entity - {$meta->name}, you should specify slugField annotation property");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config) {
        $class = $meta->getReflectionClass();
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // sluggable property
            if ($sluggable = $this->reader->getPropertyAnnotation($property, self::SLUGGABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find sluggable [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$meta->name}");
                }
                if (!is_null($sluggable->slugField) and !$meta->hasField($sluggable->slugField)) {

                    throw new InvalidMappingException(sprintf('The "%s" property - which is defined as the "slugField" for the "%s" property - does not exist or is not mapped to Doctrine in "%s"', $sluggable->slugField, $field, $meta->name));
                }
                $config['fields'][$sluggable->slugField][] = array('field' => $field, 'position' => $sluggable->position, 'slugField' => $sluggable->slugField);
            }
            // slug property
            if ($slug = $this->reader->getPropertyAnnotation($property, self::SLUG)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find slug [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' in class - {$meta->name}");
                }
                // process slug handlers
                if (is_array($slug->handlers) && $slug->handlers) {
                    foreach ($slug->handlers as $handler) {
                        if (!$handler instanceof SlugHandler) {
                            throw new InvalidMappingException("SlugHandler: {$handler} should be instance of SlugHandler annotation in entity - {$meta->name}");
                        }
                        if (!strlen($handler->class)) {
                            throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->name}");
                        }
                        $class = $handler->class;
                        $config['handlers'][$class] = array();
                        foreach ((array)$handler->options as $option) {
                            if (!$option instanceof SlugHandlerOption) {
                                throw new InvalidMappingException("SlugHandlerOption: {$option} should be instance of SlugHandlerOption annotation in entity - {$meta->name}");
                            }
                            if (!strlen($option->name)) {
                                throw new InvalidMappingException("SlugHandlerOption name: {$option->name} should be valid name in entity - {$meta->name}");
                            }
                            $config['handlers'][$class][$option->name] = $option->value;
                        }
                        $class::validate($config['handlers'][$class], $meta);
                    }
                }

                $config['slugFields'][$field]['slug'] = $field;
                $config['slugFields'][$field]['style'] = $slug->style;
                $config['slugFields'][$field]['updatable'] = $slug->updatable;
                $config['slugFields'][$field]['unique'] = $slug->unique;
                $config['slugFields'][$field]['separator'] = $slug->separator;
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
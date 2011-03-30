<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver,
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
 * @package Gedmo.Sortable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to mark field as sluggable and include it in slug building
     */
    const ANNOTATION_SORT_IDENTIFIER = 'Gedmo\Sortable\Mapping\SortIdentifier';

    /**
     * Annotation to identify field as one which holds the slug
     * together with slug options
     */
    const ANNOTATION_SORT = 'Gedmo\Sortable\Mapping\Sort';

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validTypes = array(
        'int'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if (isset($config['sort_identifier']) && !isset($config['sort'])) {
            throw new InvalidMappingException("you have specified SortIdentifier mapping information but have forgotten to add Sort mapping information in '{$meta->name}'");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Sortable\Mapping\\', 'gedmo');

        $class = $meta->getReflectionClass();
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // sortable identifier
            if ($sortIdentifier = $reader->getPropertyAnnotation($property, self::ANNOTATION_SORT_IDENTIFIER)) {
                $config['sort_identifier'] = $property->getName();
            }
            // sort property
            if ($slug = $reader->getPropertyAnnotation($property, self::ANNOTATION_SORT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find sort [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Cannot use field - [{$field}] for sort storage, type is not valid and must be 'int' in class - {$meta->name}");
                }
                if (isset($config['sort'])) {
                    throw new InvalidMappingException("There cannot be two sort fields: [{$slugField}] and [{$config['slug']}], in class - {$meta->name}.");
                }

                $config['sort'] = $field;
            }
        }
    }

    /**
     * Checks if $field type is valid as Sortable field
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
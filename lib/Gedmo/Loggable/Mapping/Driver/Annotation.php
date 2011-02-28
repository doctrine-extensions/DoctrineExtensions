<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to define the tree type
     */
    const ANNOTATION_LOGGABLE = 'Gedmo\Loggable\Mapping\Loggable';

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $actions = array(
        'create', 'update', 'delete'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config)
    {
        if (isset($config['actions']) && is_array($config['actions'])) {
            foreach ($config['actions'] as $action) {
                if (!in_array($action, $this->actions)) {
                    throw new InvalidMappingException("Action {$action} for class: {$meta->name} is invalid");   
                }
            }
        }

        if (isset($config['actions']) && !is_array($config['actions'])) {
            throw new InvalidMappingException("Actions for class: {$meta->name} should be an array");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Loggable\Mapping\\', 'gedmo');

        $class = $meta->getReflectionClass();
        // class annotations
        $classAnnotations = $reader->getClassAnnotations($class);
        if (isset($classAnnotations[self::ANNOTATION_LOGGABLE])) {
            $annot = $classAnnotations[self::ANNOTATION_LOGGABLE];
            $config['actions'] = $annot->actions;
        }
    }
}
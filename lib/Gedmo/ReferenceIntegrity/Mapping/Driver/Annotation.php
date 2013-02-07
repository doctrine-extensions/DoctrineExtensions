<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\ReferenceIntegrityAction;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * This is an annotation mapping driver for ReferenceIntegrity
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @package Gedmo.ReferenceIntegrity.Mapping.Driver
 * @subpackage Annotation
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identify the fields which manages the reference integrity
     */
    const REFERENCE_INTEGRITY = 'Gedmo\\Mapping\\Annotation\\ReferenceIntegrity';

    /**
     * ReferenceIntegrityAction extension annotation
     */
    const ACTION = 'Gedmo\\Mapping\\Annotation\\ReferenceIntegrityAction';

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $validator = new Validator();
        $reflClass = $this->getMetaReflectionClass($meta);

        foreach ($reflClass->getProperties() as $reflProperty) {
            if ($referenceIntegrity = $this->reader->getPropertyAnnotation($reflProperty, self::REFERENCE_INTEGRITY)) {
                $property = $reflProperty->getName();
                if (!$meta->hasField($property)) {
                    throw new InvalidMappingException(
                        sprintf(
                            "Unable to find reference integrity [%s] as mapped property in entity - %s",
                            $property,
                            $meta->name
                        )
                    );
                }
                if (is_array($referenceIntegrity->actions) && $referenceIntegrity->actions) {
                    foreach ($referenceIntegrity->actions as $action) {
                        if (!$action instanceof ReferenceIntegrityAction) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "ReferenceIntegrityAction: %s
                                    should be instance of ReferenceIntegrityAction annotation in entity - %s",
                                    $action,
                                    $meta->name
                                )
                            );
                        }

                        if (!in_array($action->action, $validator->getIntegrityActions())) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Field - [%s] does not have a valid integrity option, [%s] in class - %s",
                                    $property,
                                    implode($validator->getIntegrityActions(), ', '),
                                    $meta->name
                                )
                            );
                        }

                        $config['referenceIntegrities'][$property][$action->field] = $action->action;
                    }
                }
            }
        }
    }
}

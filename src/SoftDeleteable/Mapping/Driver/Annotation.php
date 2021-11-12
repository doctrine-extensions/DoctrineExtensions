<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\SoftDeleteable\Mapping\Validator;

/**
 * This is an annotation mapping driver for SoftDeleteable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for SoftDeleteable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    public const SOFT_DELETEABLE = SoftDeleteable::class;

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if (null !== $class && $annot = $this->reader->getClassAnnotation($class, self::SOFT_DELETEABLE)) {
            $config['softDeleteable'] = true;

            Validator::validateField($meta, $annot->fieldName);

            $config['fieldName'] = $annot->fieldName;

            $config['timeAware'] = false;
            if (isset($annot->timeAware)) {
                if (!is_bool($annot->timeAware)) {
                    throw new InvalidMappingException('timeAware must be boolean. '.gettype($annot->timeAware).' provided.');
                }
                $config['timeAware'] = $annot->timeAware;
            }

            $config['hardDelete'] = true;
            if (isset($annot->hardDelete)) {
                if (!is_bool($annot->hardDelete)) {
                    throw new InvalidMappingException('hardDelete must be boolean. '.gettype($annot->hardDelete).' provided.');
                }
                $config['hardDelete'] = $annot->hardDelete;
            }
        }

        $this->validateFullMetadata($meta, $config);
    }
}

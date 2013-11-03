<?php

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\SoftDeleteable\Mapping\Validator;

/**
 * This is an annotation mapping driver for SoftDeleteable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for SoftDeleteable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    const SOFT_DELETEABLE = 'Gedmo\\Mapping\\Annotation\\SoftDeleteable';

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($class !== null && $annot = $this->reader->getClassAnnotation($class, self::SOFT_DELETEABLE)) {
            $config['softDeleteable'] = true;

            Validator::validateField($meta, $annot->fieldName);

            $config['fieldName'] = $annot->fieldName;

            $config['timeAware'] = false;
            if(isset($annot->timeAware)){
                if (!is_bool($annot->timeAware)) {
                    throw new InvalidMappingException("timeAware must be boolean. ".gettype($annot->timeAware)." provided.");
                }
                $config['timeAware'] = $annot->timeAware;
            }
        }

        $this->validateFullMetadata($meta, $config);
    }
}

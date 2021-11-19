<?php

namespace Gedmo\SoftDeleteable\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * This class is used to validate mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Validator
{
    /**
     * List of types which are valid for timestamp
     *
     * @var array
     */
    public static $validTypes = [
        'date',
        'date_immutable',
        'time',
        'time_immutable',
        'datetime',
        'datetime_immutable',
        'datetimetz',
        'datetimetz_immutable',
        'timestamp',
    ];

    public static function validateField(ClassMetadata $meta, $field)
    {
        if ($meta->isMappedSuperclass) {
            return;
        }

        $fieldMapping = $meta->getFieldMapping($field);

        if (!in_array($fieldMapping['type'], self::$validTypes)) {
            throw new InvalidMappingException(sprintf('Field "%s" (type "%s") must be of one of the following types: "%s" in entity %s', $field, $fieldMapping['type'], implode(', ', self::$validTypes), $meta->getName()));
        }
    }
}

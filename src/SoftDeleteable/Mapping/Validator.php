<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * This class is used to validate mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class Validator
{
    /**
     * List of types which are valid for timestamp
     *
     * @var string[]
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

    /**
     * @param ClassMetadata<object> $meta
     * @param mixed                 $field
     *
     * @return void
     */
    public static function validateField(ClassMetadata $meta, $field)
    {
        if ($meta->isMappedSuperclass) {
            return;
        }

        $fieldMapping = $meta->getFieldMapping($field);

        if (!in_array($fieldMapping->type ?? $fieldMapping['type'], self::$validTypes, true)) {
            throw new InvalidMappingException(sprintf('Field "%s" (type "%s") must be of one of the following types: "%s" in entity %s', $field, $fieldMapping->type ?? $fieldMapping['type'], implode(', ', self::$validTypes), $meta->getName()));
        }
    }
}

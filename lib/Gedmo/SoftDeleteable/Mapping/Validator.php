<?php

namespace Gedmo\SoftDeleteable\Mapping;

use Gedmo\Exception\InvalidMappingException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

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
    public static $validTypes = array(
        'date',
        'time',
        'datetime',
        'datetimetz',
        'timestamp',
        'zenddate'
    );


    public static function validateField(ClassMetadata $meta, $field)
    {
        if ($meta->isMappedSuperclass) {
            return;
        }

        $fieldMapping = $meta->getFieldMapping($field);

        if (!in_array($fieldMapping['type'], self::$validTypes)) {
            throw new InvalidMappingException(sprintf('Field "%s" must be of one of the following types: "%s"',
                $fieldMapping['type'],
                implode(', ', self::$validTypes)));
        }
    }
}

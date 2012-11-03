<?php

namespace Gedmo\SoftDeleteable\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Exception\InvalidMappingException;

/**
 * This class is used to validate mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.SoftDeleteable.Mapping
 * @subpackage Validator
 * @link http://www.gediminasm.org
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
        'timestamp',
        'zenddate'
    );


    public static function validateField(ClassMetadataInfo $meta, $field)
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

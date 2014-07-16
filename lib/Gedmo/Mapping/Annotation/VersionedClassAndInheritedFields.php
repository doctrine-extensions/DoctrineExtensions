<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * VersionedClassAndInheritedFields annotation for Loggable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Krzysztof Cholewka <cholewka.krzysztof@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class VersionedClassAndInheritedFields extends Annotation
{
    /**
     * @var array
     */
    protected $fieldList;

    /**
     * @return array
     */
    public function getFieldList()
    {
        return $this->fieldList;
    }
}


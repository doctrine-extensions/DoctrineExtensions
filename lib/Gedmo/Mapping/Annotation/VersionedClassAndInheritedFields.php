<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Loggable annotation for Loggable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Krzysztof Cholewka <cholewka.krzysztof@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class VersionedClassAndInheritedFields extends Annotation
{
    protected $fieldList;

    public function getFiledList()
    {
        return $this->fieldList;
    }
}


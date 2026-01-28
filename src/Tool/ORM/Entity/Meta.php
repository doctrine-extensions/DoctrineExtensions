<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor;

class Meta
{
    /**
     * @param ClassMetadata<object> $meta
     */
    public static function getProperty(ClassMetadata $meta, string $propertyName): \ReflectionProperty|PropertyAccessor|null
    {
        if (method_exists(ClassMetadata::class, 'getPropertyAccessor')) {
            return $meta->getPropertyAccessor($propertyName);
        }

        return $meta->getReflectionProperty($propertyName);
    }
}

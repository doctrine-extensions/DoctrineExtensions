<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Entity;

use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor;
use Doctrine\Persistence\Mapping\ClassMetadata;

class Meta
{
    /**
     * @param ClassMetadata<object> $meta
     *
     * @return \ReflectionProperty|PropertyAccessor|null
     */
    public static function getProperty(ClassMetadata $meta, string $propertyName)
    {
        if (method_exists($meta, 'getPropertyAccessor')) {
            return $meta->getPropertyAccessor($propertyName);
        }

        if (method_exists($meta, 'getReflectionProperty')) {
            return $meta->getReflectionProperty($propertyName);
        }

        throw new \RuntimeException('Unable to determine property accessor for class '.get_class($meta));
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool;

use Doctrine\Common\Util\ClassUtils as CommonClassUtils;

/**
 * Utility class for Doctrine Common proxies.
 */
final class ClassUtils
{
    private function __construct()
    {
    }

    /**
     * Gets the real class name of an object (even if it's a proxy).
     *
     * If doctrine/common is not installed, this method behaves like {@see get_class()}.
     *
     * @param TObject $object
     *
     * @return class-string<TObject>
     *
     * @template TObject of object
     */
    public static function getClass(object $object): string
    {
        if (class_exists(CommonClassUtils::class)) {
            return CommonClassUtils::getClass($object);
        }

        return get_class($object);
    }
}

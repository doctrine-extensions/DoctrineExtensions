<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Helper class to address the deprecation of string sort directions in ORM 3.7.
 *
 * @internal
 */
final class SortDirectionCompat
{
    private static ?bool $enumSupported = null;

    /**
     * Converts a sort direction string to the value expected by QueryBuilder::orderBy():
     * any value other than "asc" (case-insensitive) is descending.
     *
     * @param string $direction
     *
     * @return string|\SortDirection
     */
    public static function convert($direction)
    {
        $ascending = 'asc' === strtolower($direction);

        // ORM 3.7+ accepts (and expects) a SortDirection instance
        self::$enumSupported ??= false !== strpos((string) (new \ReflectionMethod(QueryBuilder::class, 'orderBy'))->getParameters()[1]->getType(), 'SortDirection');

        if (!self::$enumSupported) {
            return $ascending ? 'ASC' : 'DESC';
        }

        return $ascending ? \SortDirection::Ascending : \SortDirection::Descending;
    }
}

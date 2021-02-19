<?php

namespace Gedmo;

/**
 * The Gedmo class make it easier to get the root path of gedmo.
 *
 * @author Alexander Schranz <alexander@sulu.io>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Gedmo
{
    /**
     * Return the Gedmo root path.
     *
     * @return string
     */
    public static function getRootPath()
    {
        return __DIR__;
    }
}

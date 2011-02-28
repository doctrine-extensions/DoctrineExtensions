<?php

namespace Gedmo\Loggable;

/**
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @subpackage Configuration
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Configuration
{
    private static $user;

    public static function setUser($user)
    {
        self::$user = $user;
    }

    public static function getUser()
    {
        return self::$user;
    }

}
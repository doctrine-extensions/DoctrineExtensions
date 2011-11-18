<?php

namespace Gedmo;

use Gedmo\Exception\DependentComponentNotFoundException;
use Gedmo\Exception\IncompatibleComponentVersionException;

/**
 * Version class allows to checking the dependencies required
 * and the current version of doctrine extensions
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage Version
 * @package Gedmo
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Version
{
    /**
     * Current version of extensions
     */
    const VERSION = '2.2.0-DEV';

    /**
     * Checks the dependent ORM library components
     * for compatibility
     *
     * @throws DependentComponentNotFoundException
     * @throws IncompatibleComponentVersionException
     */
    public static function checkORMDependencies()
    {
        // doctrine common library
        if (!class_exists('Doctrine\\Common\\Version')) {
            throw new DependentComponentNotFoundException("Doctrine\\Common library is either not registered by autoloader or not installed");
        }
        if (\Doctrine\Common\Version::compare(self::VERSION) > 0) {
            throw new IncompatibleComponentVersionException("Doctrine\\Common library is older than expected for these extensions");
        }

        // doctrine dbal library
        if (!class_exists('Doctrine\\DBAL\\Version')) {
            throw new DependentComponentNotFoundException("Doctrine\\DBAL library is either not registered by autoloader or not installed");
        }
        if (\Doctrine\DBAL\Version::compare(self::VERSION) > 0) {
            throw new IncompatibleComponentVersionException("Doctrine\\DBAL library is older than expected for these extensions");
        }

        // doctrine ORM library
        if (!class_exists('Doctrine\\ORM\\Version')) {
            throw new DependentComponentNotFoundException("Doctrine\\ORM library is either not registered by autoloader or not installed");
        }
        if (\Doctrine\ORM\Version::compare(self::VERSION) > 0) {
            throw new IncompatibleComponentVersionException("Doctrine\\ORM library is older than expected for these extensions");
        }
    }

    /**
     * Checks the dependent ODM MongoDB library components
     * for compatibility
     *
     * @throws DependentComponentNotFoundException
     * @throws IncompatibleComponentVersionException
     */
    public static function checkODMMongoDBDependencies()
    {
        // doctrine common library
        if (!class_exists('Doctrine\\Common\\Version')) {
            throw new DependentComponentNotFoundException("Doctrine\\Common library is either not registered by autoloader or not installed");
        }
        if (\Doctrine\Common\Version::compare(self::VERSION) > 0) {
            throw new IncompatibleComponentVersionException("Doctrine\\Common library is older than expected for these extensions");
        }

        // doctrine mongodb library
        if (!class_exists('Doctrine\\MongoDB\\Database')) {
            throw new DependentComponentNotFoundException("Doctrine\\MongoDB library is either not registered by autoloader or not installed");
        }

        // doctrine ODM MongoDB library
        if (!class_exists('Doctrine\\ODM\\MongoDB\\Version')) {
            throw new DependentComponentNotFoundException("Doctrine\\ODM\\MongoDB library is either not registered by autoloader or not installed");
        }
        if (\Doctrine\ODM\MongoDB\Version::compare('1.0.0BETA3-DEV') > 0) {
            throw new IncompatibleComponentVersionException("Doctrine\\ODM\\MongoDB library is older than expected for these extensions");
        }
    }
}
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
    const VERSION = '2.3.0-DEV';
}
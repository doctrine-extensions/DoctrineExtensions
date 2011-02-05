<?php

namespace Gedmo\Exception;

use Gedmo\Exception;

/**
 * InvalidMappingException
 * 
 * Triggered when mapping user argument is not
 * valid or incomplete.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Exception
 * @subpackage InvalidMappingException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InvalidMappingException 
    extends InvalidArgumentException
    implements Exception
{}
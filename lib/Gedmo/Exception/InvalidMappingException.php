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
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InvalidMappingException extends InvalidArgumentException implements Exception
{
    /**
     * Extension configuration collected before
     * an exception was triggered
     *
     * @var array
     */
    public $currentConfig;

    public function __construct($msg, array $config = array())
    {
        $this->currentConfig = $config;
        parent::__construct($msg);
    }
}

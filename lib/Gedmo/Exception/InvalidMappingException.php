<?php

namespace Gedmo\Exception;

use Gedmo\Exception;
use Gedmo\Mapping\ExtensionMetadataInterface;

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
     * Extension metadata collected before
     * an exception was triggered
     *
     * @var \Gedmo\Mapping\ExtensionMetadataInterface
     */
    public $exm;

    public function __construct($msg, ExtensionMetadataInterface $exm = null)
    {
        $this->exm = $exm;
        parent::__construct($msg);
    }
}

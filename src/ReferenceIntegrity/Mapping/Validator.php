<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\ReferenceIntegrity\Mapping;

/**
 * This class is used to validate mapping information
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class Validator
{
    public const NULLIFY = 'nullify';
    public const PULL = 'pull';
    public const RESTRICT = 'restrict';

    /**
     * List of actions which are valid as integrity check
     *
     * @var array
     */
    private $integrityActions = [
        self::NULLIFY,
        self::PULL,
        self::RESTRICT,
    ];

    /**
     * Returns a list of available integrity actions
     *
     * @return array
     */
    public function getIntegrityActions()
    {
        return $this->integrityActions;
    }
}

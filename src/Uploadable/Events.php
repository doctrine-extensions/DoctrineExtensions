<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable;

/**
 * Container for all Gedmo Uploadable events
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Events
{
    /**
     * The uploadablePreFileProcess event occurs before a file is processed inside
     * the Uploadable listener. This means it happens before the file is validated and moved
     * to the configured path.
     *
     * @var string
     */
    public const uploadablePreFileProcess = 'uploadablePreFileProcess';
    /**
     * The uploadablePostFileProcess event occurs after a file is processed inside
     * the Uploadable listener. This means it happens after the file is validated and moved
     * to the configured path.
     *
     * @var string
     */
    public const uploadablePostFileProcess = 'uploadablePostFileProcess';

    private function __construct()
    {
    }
}

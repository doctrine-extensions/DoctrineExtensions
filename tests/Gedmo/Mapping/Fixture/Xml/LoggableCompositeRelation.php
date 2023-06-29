<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class LoggableCompositeRelation
{
    /**
     * @var Loggable
     */
    private $one;

    /**
     * @var int
     */
    private $two;

    /**
     * @var string
     */
    private $title;
}

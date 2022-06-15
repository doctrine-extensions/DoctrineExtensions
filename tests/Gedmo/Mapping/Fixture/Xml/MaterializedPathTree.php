<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class MaterializedPathTree
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $path;

    /**
     * @var \DateTime|null
     */
    private $lockTime;

    /**
     * @var string
     */
    private $pathHash;

    /**
     * @var MaterializedPathTree
     */
    private $parent;

    /**
     * @var int
     */
    private $level;
}

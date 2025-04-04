<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class NestedTree
{
    private ?int $id = null;

    private ?string $name = null;

    private ?NestedTree $parent = null;

    private ?int $root = null;

    private ?int $level = null;

    private ?int $left = null;

    private ?int $right = null;
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class Sluggable
{
    private ?int $id = null;

    private ?string $title = null;

    private ?string $code = null;

    private ?string $ean = null;

    private ?string $slug = null;

    private ?Sluggable $parent = null;
}

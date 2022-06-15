<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Unmapped;

use Gedmo\Mapping\Annotation\Timestampable as Tmsp;

class Timestampable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     * @Tmsp(on="create")
     */
    private $created;
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;
use Gedmo\Mapping\Annotation as Gedmo;

class SeparateDoctrineMapping
{
    /**
     * @var int
     */
    private $id;

    private $title;

    /**
     * @var \DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    private $created;
    /**
     * @var \DateTime
     */
    #[Gedmo\Timestampable(on: 'update')]
    private $updated;
}

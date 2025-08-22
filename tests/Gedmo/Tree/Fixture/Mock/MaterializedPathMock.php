<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Mock;

use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tree\Strategy\ODM\MongoDB\MaterializedPath;

/**
 * Mock to test concurrency in MaterializedPath strategy
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathMock extends MaterializedPath
{
    /**
     * @var bool
     */
    public $releaseLocks = false;

    protected function releaseTreeLocks(ObjectManager $om, AdapterInterface $ea): void
    {
        if ($this->releaseLocks) {
            parent::releaseTreeLocks($om, $ea);
        }
    }
}

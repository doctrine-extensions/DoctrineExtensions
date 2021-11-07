<?php

/**
 * Mock to test concurrency in MaterializedPath strategy
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gedmo\Tests\Tree\Fixture\Mock;

use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tree\Strategy\ODM\MongoDB\MaterializedPath;

class MaterializedPathMock extends MaterializedPath
{
    public $releaseLocks = false;

    protected function releaseTreeLocks(ObjectManager $om, AdapterInterface $ea)
    {
        if ($this->releaseLocks) {
            parent::releaseTreeLocks($om, $ea);
        }
    }
}

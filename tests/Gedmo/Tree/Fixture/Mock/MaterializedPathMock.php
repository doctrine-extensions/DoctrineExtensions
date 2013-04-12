<?php

/**
 * Mock to test concurrency in MaterializedPath strategy
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Tree\Fixture\Mock;

use Gedmo\Tree\Strategy\ODM\MongoDB\MaterializedPath;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

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

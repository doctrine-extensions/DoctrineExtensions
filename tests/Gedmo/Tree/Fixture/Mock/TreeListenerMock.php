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
use Gedmo\Tree\TreeListener;

class TreeListenerMock extends TreeListener
{
    public $releaseLocks = false;
    protected $strategy = null;

    public function getStrategy(ObjectManager $om, $class)
    {
        if (null === $this->strategy) {
            $this->strategy = new MaterializedPathMock($this);
            $this->strategy->releaseLock = $this->releaseLocks;
        }

        return $this->strategy;
    }

    protected function getStrategiesUsedForObjects(array $classes)
    {
        if (null === $this->strategy) {
            $this->strategy = new MaterializedPathMock($this);
            $this->strategy->releaseLock = $this->releaseLocks;
        }

        return ['materializedPath' => $this->strategy];
    }

    public function setReleaseLocks($bool)
    {
        $this->strategy->releaseLocks = $bool;
    }
}

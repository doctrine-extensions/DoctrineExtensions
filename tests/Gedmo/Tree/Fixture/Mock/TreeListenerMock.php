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
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;

/**
 * Mock to test concurrency in MaterializedPath strategy
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class TreeListenerMock extends TreeListener
{
    /**
     * @var bool
     */
    public $releaseLocks = false;

    /**
     * @var MaterializedPathMock
     */
    protected $strategy;

    public function getStrategy(ObjectManager $om, $class)
    {
        if (null === $this->strategy) {
            $this->strategy = new MaterializedPathMock($this);
            $this->strategy->releaseLocks = $this->releaseLocks;
        }

        return $this->strategy;
    }

    public function setReleaseLocks(bool $bool): void
    {
        $this->strategy->releaseLocks = $bool;
    }

    protected function getStrategiesUsedForObjects(array $classes): array
    {
        if (null === $this->strategy) {
            $this->strategy = new MaterializedPathMock($this);
            $this->strategy->releaseLocks = $this->releaseLocks;
        }

        return ['materializedPath' => $this->strategy];
    }
}

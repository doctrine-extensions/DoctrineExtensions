<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Mapping\Fixture\Xml\NestedTree;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class NestedTreeMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var TreeListener
     */
    private $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->tree = new TreeListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            NestedTree::class,
        ], $chain);
    }

    public function testTreeMetadata(): void
    {
        $meta = $this->em->getClassMetadata(NestedTree::class);
        $config = $this->tree->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('nested', $config['strategy']);
        static::assertArrayHasKey('left', $config);
        static::assertSame('left', $config['left']);
        static::assertArrayHasKey('right', $config);
        static::assertSame('right', $config['right']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('root', $config);
        static::assertSame('root', $config['root']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
    }
}

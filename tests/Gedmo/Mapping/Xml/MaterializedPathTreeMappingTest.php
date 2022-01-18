<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Mapping\Fixture\Xml\MaterializedPathTree;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathTreeMappingTest extends BaseTestCaseOM
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

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');
        $chain->addDriver($annotationDriver, 'Gedmo\Tree');

        $this->tree = new TreeListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            MaterializedPathTree::class,
        ], $chain);
    }

    public function testTreeMetadata(): void
    {
        $meta = $this->em->getClassMetadata(MaterializedPathTree::class);
        $config = $this->tree->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('materializedPath', $config['strategy']);
        static::assertArrayHasKey('activate_locking', $config);
        static::assertTrue($config['activate_locking']);
        static::assertArrayHasKey('locking_timeout', $config);
        static::assertSame(10, $config['locking_timeout']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('path_source', $config);
        static::assertSame('title', $config['path_source']);
        static::assertArrayHasKey('path', $config);
        static::assertSame('path', $config['path']);
        static::assertArrayHasKey('lock_time', $config);
        static::assertSame('lockTime', $config['lock_time']);
        static::assertArrayHasKey('path_hash', $config);
        static::assertSame('pathHash', $config['path_hash']);
    }
}

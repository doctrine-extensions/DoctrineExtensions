<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathTreeMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Tree\TreeListener
     */
    private $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');
        $chain->addDriver($annotationDriver, 'Gedmo\Tree');

        $this->tree = new TreeListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Tests\Mapping\Fixture\Xml\MaterializedPathTree',
        ], $chain);
    }

    public function testTreeMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\MaterializedPathTree');
        $config = $this->tree->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('strategy', $config);
        static::assertEquals('materializedPath', $config['strategy']);
        static::assertArrayHasKey('activate_locking', $config);
        static::assertTrue($config['activate_locking']);
        static::assertArrayHasKey('locking_timeout', $config);
        static::assertEquals(10, $config['locking_timeout']);
        static::assertArrayHasKey('level', $config);
        static::assertEquals('level', $config['level']);
        static::assertArrayHasKey('parent', $config);
        static::assertEquals('parent', $config['parent']);
        static::assertArrayHasKey('path_source', $config);
        static::assertEquals('title', $config['path_source']);
        static::assertArrayHasKey('path', $config);
        static::assertEquals('path', $config['path']);
        static::assertArrayHasKey('lock_time', $config);
        static::assertEquals('lockTime', $config['lock_time']);
    }
}

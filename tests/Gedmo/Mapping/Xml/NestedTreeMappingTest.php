<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Tree\TreeListener
     */
    private $tree;

    public function setUp(): void
    {
        parent::setUp();

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->tree = new TreeListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Tests\Mapping\Fixture\Xml\NestedTree',
        ], $chain);
    }

    public function testTreeMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\NestedTree');
        $config = $this->tree->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('strategy', $config);
        $this->assertEquals('nested', $config['strategy']);
        $this->assertArrayHasKey('left', $config);
        $this->assertEquals('left', $config['left']);
        $this->assertArrayHasKey('right', $config);
        $this->assertEquals('right', $config['right']);
        $this->assertArrayHasKey('level', $config);
        $this->assertEquals('level', $config['level']);
        $this->assertArrayHasKey('root', $config);
        $this->assertEquals('root', $config['root']);
        $this->assertArrayHasKey('parent', $config);
        $this->assertEquals('parent', $config['parent']);
    }
}

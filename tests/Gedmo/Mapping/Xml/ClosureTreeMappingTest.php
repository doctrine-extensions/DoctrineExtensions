<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Tree\TreeListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Tree\TreeListener
     */
    private $tree;

    public function setUp()
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain;
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');
        $chain->addDriver($annotationDriver, 'Gedmo\Tree');

        $this->tree = new TreeListener;
        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Xml\ClosureTree',
            'Mapping\Fixture\ClosureTreeClosure'
        ), $chain);
    }

    public function testTreeMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\ClosureTree');
        $config = $this->tree->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('strategy', $config);
        $this->assertEquals('closure', $config['strategy']);
        $this->assertArrayHasKey('closure', $config);
        $this->assertEquals('Mapping\Fixture\ClosureTreeClosure', $config['closure']);
        $this->assertArrayHasKey('parent', $config);
        $this->assertEquals('parent', $config['parent']);
    }
}

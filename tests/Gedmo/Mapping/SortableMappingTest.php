<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Sortable\SortableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for sortable extension
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Sortable\SortableListener
     */
    private $sortable;

    public function setUp()
    {
        parent::setUp();
        
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain;
        $chain->addDriver($yamlDriver, 'Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');

        $this->sortable = new SortableListener;
        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->sortable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Yaml\Sortable',
            'Mapping\Fixture\SortableGroup'
        ), $chain);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Yaml\Sortable');
        $config = $this->sortable->getConfiguration($this->em, $meta->name);
        
        $this->assertArrayHasKey('position', $config);
        $this->assertEquals('position', $config['position']);
        $this->assertArrayHasKey('groups', $config);
        $this->assertCount(3, $config['groups']);
        $this->assertEquals('grouping', $config['groups'][0]);
        $this->assertEquals('sortable_group', $config['groups'][1]);
        $this->assertEquals('sortable_groups', $config['groups'][2]);
    }
}

<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\SoftDeletable\SoftDeletableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeletable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeletableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\SoftDeletable\SoftDeletableListener
     */
    private $softDeletable;

    public function setUp()
    {
        parent::setUp();
        
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain;
        $chain->addDriver($yamlDriver, 'Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');

        $this->softDeletable = new SoftDeletableListener();
        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->softDeletable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Yaml\SoftDeletable',
            'Mapping\Fixture\SoftDeletable'
        ), $chain);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Yaml\SoftDeletable');
        $config = $this->softDeletable->getConfiguration($this->em, $meta->name);
        
        $this->assertArrayHasKey('softDeletable', $config);
        $this->assertTrue($config['softDeletable']);
        $this->assertArrayHasKey('timeAware', $config);
        $this->assertFalse($config['timeAware']);
        $this->assertArrayHasKey('fieldName', $config);
        $this->assertEquals('deletedAt', $config['fieldName']);
    }
}

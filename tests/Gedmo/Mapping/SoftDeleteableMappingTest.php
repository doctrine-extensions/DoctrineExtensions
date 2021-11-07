<?php

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\SoftDeleteable\SoftDeleteableListener
     */
    private $softDeleteable;

    public function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain();
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->softDeleteable = new SoftDeleteableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->softDeleteable);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Tests\Mapping\Fixture\Yaml\SoftDeleteable',
            'Gedmo\Tests\Mapping\Fixture\SoftDeleteable',
        ], $chain);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Yaml\SoftDeleteable');
        $config = $this->softDeleteable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('softDeleteable', $config);
        $this->assertTrue($config['softDeleteable']);
        $this->assertArrayHasKey('timeAware', $config);
        $this->assertFalse($config['timeAware']);
        $this->assertArrayHasKey('fieldName', $config);
        $this->assertEquals('deletedAt', $config['fieldName']);
    }
}

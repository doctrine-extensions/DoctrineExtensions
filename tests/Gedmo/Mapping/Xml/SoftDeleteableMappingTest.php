<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
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

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->softDeleteable = new SoftDeleteableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->softDeleteable);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Tests\Mapping\Fixture\Xml\SoftDeleteable',
            'Gedmo\Tests\Mapping\Fixture\SoftDeleteable',
        ], $chain);
    }

    public function testMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\SoftDeleteable');
        $config = $this->softDeleteable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('softDeleteable', $config);
        static::assertTrue($config['softDeleteable']);
        static::assertArrayHasKey('timeAware', $config);
        static::assertFalse($config['timeAware']);
        static::assertArrayHasKey('fieldName', $config);
        static::assertEquals('deletedAt', $config['fieldName']);
    }
}

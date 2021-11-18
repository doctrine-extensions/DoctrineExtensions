<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Mapping\Fixture\SortableGroup;
use Gedmo\Tests\Mapping\Fixture\Yaml\Sortable;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for sortable extension
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SortableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Sortable\SortableListener
     */
    private $sortable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain();
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->sortable = new SortableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->sortable);

        $this->em = $this->getMockSqliteEntityManager([
            Sortable::class,
            SortableGroup::class,
        ], $chain);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(Sortable::class);
        $config = $this->sortable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('position', $config);
        static::assertSame('position', $config['position']);
        static::assertArrayHasKey('groups', $config);
        static::assertCount(3, $config['groups']);
        static::assertSame('grouping', $config['groups'][0]);
        static::assertSame('sortable_group', $config['groups'][1]);
        static::assertSame('sortable_groups', $config['groups'][2]);
    }
}

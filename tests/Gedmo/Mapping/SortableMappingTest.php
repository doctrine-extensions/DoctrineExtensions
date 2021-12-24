<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Mapping\Fixture\SortableGroup;
use Gedmo\Tests\Mapping\Fixture\Yaml\Sortable;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for sortable extension
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 */
final class SortableMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var SortableListener
     */
    private $sortable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new MappingDriverChain();
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->sortable = new SortableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->sortable);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            Sortable::class,
            SortableGroup::class,
        ], $chain);
    }

    public function testYamlMapping(): void
    {
        $meta = $this->em->getClassMetadata(Sortable::class);
        $config = $this->sortable->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('position', $config);
        static::assertSame('position', $config['position']);
        static::assertArrayHasKey('groups', $config);
        static::assertCount(3, $config['groups']);
        static::assertSame('grouping', $config['groups'][0]);
        static::assertSame('sortable_group', $config['groups'][1]);
        static::assertSame('sortable_groups', $config['groups'][2]);
    }
}

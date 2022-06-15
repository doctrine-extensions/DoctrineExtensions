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
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\Mapping\Fixture\Yaml\SoftDeleteable;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SoftDeleteableMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var SoftDeleteableListener
     */
    private $softDeleteable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new MappingDriverChain();
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->softDeleteable = new SoftDeleteableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->softDeleteable);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            SoftDeleteable::class,
            \Gedmo\Tests\Mapping\Fixture\SoftDeleteable::class,
        ], $chain);
    }

    public function testYamlMapping(): void
    {
        $meta = $this->em->getClassMetadata(SoftDeleteable::class);
        $config = $this->softDeleteable->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('softDeleteable', $config);
        static::assertTrue($config['softDeleteable']);
        static::assertArrayHasKey('timeAware', $config);
        static::assertFalse($config['timeAware']);
        static::assertArrayHasKey('fieldName', $config);
        static::assertSame('deletedAt', $config['fieldName']);
    }
}

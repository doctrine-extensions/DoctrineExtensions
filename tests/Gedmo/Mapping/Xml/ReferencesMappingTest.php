<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\References\ReferencesListener;
use Gedmo\Tests\Mapping\Fixture\Xml\References;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ReferencesMappingTest extends BaseTestCaseOM
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ReferencesListener
     */
    private $referencesListener;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__ . '/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');

        $this->referencesListener = new ReferencesListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->referencesListener);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            References::class,
        ], $chain);
    }

    public function testMetadata(): void
    {
        $meta = $this->em->getClassMetadata(References::class);
        $config = $this->referencesListener->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('referenceMany', $config);
        static::assertArrayHasKey('useObjectClass', $config);
        static::assertEquals(References::class, $config['useObjectClass']);
        $configInternal = $config['referenceMany'];
        static::assertArrayHasKey('users', $configInternal);
        $configUsers = $configInternal['users'];
        static::assertArrayHasKey('field', $configUsers);
        static::assertArrayHasKey('type', $configUsers);
        static::assertEquals('document', $configUsers['type']);
        static::assertArrayHasKey('class', $configUsers);
        static::assertArrayHasKey('identifier', $configUsers);
        static::assertArrayHasKey('mappedBy', $configUsers);
        static::assertEquals('reference', $configUsers['mappedBy']);
    }
}

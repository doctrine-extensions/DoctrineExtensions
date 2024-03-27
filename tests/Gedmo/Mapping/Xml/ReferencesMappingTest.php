<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\Driver\ORM\XmlDriver;
use Gedmo\References\ReferencesListener;
use Gedmo\Tests\Mapping\Fixture\Xml\References;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * @author Guillermo Fuentes <guillermofuentesquijada@gmail.com>
 */
final class ReferencesMappingTest extends BaseTestCaseOM
{
    private EntityManager $em;

    private ReferencesListener $referencesListener;

    protected function setUp(): void
    {
        parent::setUp();

        $annotationDriver = new AttributeDriver([]);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

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
        static::assertSame(References::class, $config['useObjectClass']);
        $configInternal = $config['referenceMany'];
        static::assertArrayHasKey('users', $configInternal);
        $configUsers = $configInternal['users'];
        static::assertArrayHasKey('field', $configUsers);
        static::assertArrayHasKey('type', $configUsers);
        static::assertSame('document', $configUsers['type']);
        static::assertArrayHasKey('class', $configUsers);
        static::assertArrayHasKey('identifier', $configUsers);
        static::assertArrayHasKey('mappedBy', $configUsers);
        static::assertSame('reference', $configUsers['mappedBy']);
    }
}

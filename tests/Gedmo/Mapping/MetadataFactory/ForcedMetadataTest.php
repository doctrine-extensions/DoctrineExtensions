<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\MetadataFactory;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Tests\Mapping\Fixture\Unmapped\Timestampable;
use Gedmo\Timestampable\TimestampableListener;
use PHPUnit\Framework\TestCase;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ForcedMetadataTest extends TestCase
{
    /**
     * @var TimestampableListener
     */
    private $timestampable;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(
            new AnnotationDriver($_ENV['annotation_reader'])
        );

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new EventManager();
        $this->timestampable = new TimestampableListener();
        $this->timestampable->setAnnotationReader($_ENV['annotation_reader']);
        $evm->addEventSubscriber($this->timestampable);
        $connection = DriverManager::getConnection($conn, $config);
        $this->em = new EntityManager($connection, $config, $evm);
    }

    public function testShouldWork(): void
    {
        $this->prepare();

        // driver falls back to annotation driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            Timestampable::class
        );
        static::assertTrue(isset($conf['create']));

        $test = new Timestampable();
        $this->em->persist($test);
        $this->em->flush();

        $id = $this->em
            ->getClassMetadata(Timestampable::class)
            ->getReflectionProperty('id')
            ->getValue($test)
        ;
        static::assertNotEmpty($id);
    }

    private function prepare(): void
    {
        $cmf = $this->em->getMetadataFactory();
        $metadata = new ClassMetadata(Timestampable::class);
        $id = [];
        $id['fieldName'] = 'id';
        $id['type'] = 'integer';
        $id['nullable'] = false;
        $id['columnName'] = 'id';
        $id['id'] = true;

        $metadata->mapField($id);

        $created = [];
        $created['fieldName'] = 'created';
        $created['type'] = 'datetime';
        $created['nullable'] = false;
        $created['columnName'] = 'created';

        $metadata->mapField($created);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $metadata->setIdGenerator(new IdentityGenerator(null));
        $metadata->setPrimaryTable(['name' => 'temp_test']);
        $cmf->setMetadataFor(Timestampable::class, $metadata);

        // trigger loadClassMetadata event
        $evm = $this->em->getEventManager();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $this->em);
        $evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);

        $metadata->wakeupReflection($cmf->getReflectionService());
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema([
            $this->em->getClassMetadata(Timestampable::class),
        ]);
    }
}

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
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Tests\Mapping\Fixture\Unmapped\Timestampable;
use Gedmo\Timestampable\TimestampableListener;
use PHPUnit\Framework\TestCase;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class CustomDriverTest extends TestCase
{
    private TimestampableListener $timestampable;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(new CustomDriver());

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new EventManager();
        $this->timestampable = new TimestampableListener();
        $this->timestampable->setAnnotationReader(new AttributeReader());
        $evm->addEventSubscriber($this->timestampable);
        $connection = DriverManager::getConnection($conn, $config);
        $this->em = new EntityManager($connection, $config, $evm);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema([
            $this->em->getClassMetadata(Timestampable::class),
        ]);
    }

    public function testShouldWork(): void
    {
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
}

class CustomDriver implements MappingDriver
{
    public function getAllClassNames(): array
    {
        return [Timestampable::class];
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
        if (Timestampable::class === $className) {
            $id = [];
            $id['fieldName'] = 'id';
            $id['type'] = 'integer';
            $id['nullable'] = false;
            $id['columnName'] = 'id';
            $id['id'] = true;

            $metadata->setIdGeneratorType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO')
            );

            $metadata->mapField($id);

            $created = [];
            $created['fieldName'] = 'created';
            $created['type'] = 'datetime';
            $created['nullable'] = false;
            $created['columnName'] = 'created';

            $metadata->mapField($created);
        }
    }

    public function isTransient($className): bool
    {
        return !in_array($className, $this->getAllClassNames(), true);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\MetadataFactory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Tests\Mapping\Fixture\Unmapped\Timestampable;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class CustomDriverTest extends \PHPUnit\Framework\TestCase
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
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(new CustomDriver());

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new \Doctrine\Common\EventManager();
        $this->timestampable = new TimestampableListener();
        $this->timestampable->setAnnotationReader($_ENV['annotation_reader']);
        $evm->addEventSubscriber($this->timestampable);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
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
        if ('Gedmo\Tests\Mapping\Fixture\Unmapped\Timestampable' === $className) {
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

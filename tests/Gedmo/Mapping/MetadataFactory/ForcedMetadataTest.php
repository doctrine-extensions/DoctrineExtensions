<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Version;
use Gedmo\Tests\Mapping\Fixture\Unmapped\Timestampable;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ForcedMetadataTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(
            new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($_ENV['annotation_reader'])
        );

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new \Doctrine\Common\EventManager();
        $this->timestampable = new \Gedmo\Timestampable\TimestampableListener();
        $this->timestampable->setAnnotationReader($_ENV['annotation_reader']);
        $evm->addEventSubscriber($this->timestampable);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    private function prepare()
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
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\IdentityGenerator(null));
        $metadata->setPrimaryTable(['name' => 'temp_test']);
        $cmf->setMetadataFor(Timestampable::class, $metadata);

        // trigger loadClassMetadata event
        $evm = $this->em->getEventManager();
        $eventArgs = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs($metadata, $this->em);
        $evm->dispatchEvent(\Doctrine\ORM\Events::loadClassMetadata, $eventArgs);

        if (Version::compare('2.3.0-dev') <= 0) {
            $metadata->wakeupReflection($cmf->getReflectionService());
        }
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema([
            $this->em->getClassMetadata(Timestampable::class),
        ]);
    }

    /**
     * @test
     */
    public function shouldWork()
    {
        $this->prepare();

        $meta = $this->em->getClassMetadata(Timestampable::class);
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

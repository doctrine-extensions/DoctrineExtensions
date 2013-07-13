<?php

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Fixture\Unmapped\Person;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Timestampable\TimestampableListener;

class CustomDriverTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Mapping\Proxy');
        $config->setMetadataDriverImpl(new CustomDriver);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $evm = new \Doctrine\Common\EventManager();
        $this->timestampable = new TimestampableListener;
        $this->timestampable->setAnnotationReader($_ENV['annotation_reader']);
        $evm->addEventSubscriber($this->timestampable);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('Fixture\Unmapped\Person'),
        ));
    }

    /**
     * @test
     */
    public function shouldBeAbleToReadAnnotationsForCustomMetadataDriver()
    {
        // driver falls back to annotation driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            'Fixture\Unmapped\Person'
        );
        $this->assertTrue(isset($conf['create']));

        $test = new Person;
        $test->setName('hello');
        $this->em->persist($test);
        $this->em->flush();

        $id = $this->em
            ->getClassMetadata('Fixture\Unmapped\Person')
            ->getReflectionProperty('id')
            ->getValue($test)
        ;
        $this->assertFalse(empty($id));
    }
}

class CustomDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array('Fixture\Unmapped\Person', 'Fixture\Unmapped\Nameable');
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === 'Fixture\Unmapped\Person') {
            $id = array();
            $id['fieldName'] = 'id';
            $id['type'] = 'integer';
            $id['nullable'] = false;
            $id['columnName'] = 'id';
            $id['id'] = true;

            $metadata->setIdGeneratorType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO')
            );

            $metadata->mapField($id);

            $created = array();
            $created['fieldName'] = 'createdAt';
            $created['type'] = 'datetime';
            $created['nullable'] = false;
            $created['columnName'] = 'created_at';

            $metadata->mapField($created);
        } elseif ($className === 'Fixture\Unmapped\Nameable') {
            $metadata->isMappedSuperclass = true;

            $name = array();
            $name['fieldName'] = 'name';
            $name['type'] = 'string';
            $name['length'] = 32;
            $name['nullable'] = false;
            $name['columnName'] = 'name';

            $metadata->mapField($name);
        }
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }
}

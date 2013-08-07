<?php

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Fixture\Unmapped\Person;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Timestampable\TimestampableListener;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\TestTool\ObjectManagerTestCase;

class CustomDriverTest extends ObjectManagerTestCase
{
    private $em;
    private $timestampable;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->timestampable = new TimestampableListener);

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl(new CustomDriverTestDriver);

        $this->em = $this->createEntityManager($evm, null, $config);
        $this->createSchema($this->em, array(
            'Gedmo\Fixture\Unmapped\Person'
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldBeAbleToReadAnnotationsForCustomMetadataDriver()
    {
        $this->em->getClassMetadata('Gedmo\Fixture\Unmapped\Person');
        // driver falls back to annotation driver
        $exm = $this->timestampable->getConfiguration($this->em, 'Gedmo\Fixture\Unmapped\Person');

        $this->assertCount(1, $fields = $exm->getFields());
        $this->assertContains('createdAt', $fields);

        $opts = $exm->getOptions('createdAt');
        $this->assertSame('create', $opts['on']);

        $test = new Person;
        $test->setName('hello');
        $this->em->persist($test);
        $this->em->flush();

        $id = $this->em
            ->getClassMetadata('Gedmo\Fixture\Unmapped\Person')
            ->getReflectionProperty('id')
            ->getValue($test)
        ;
        $this->assertFalse(empty($id));
    }
}

class CustomDriverTestDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array('Gedmo\Fixture\Unmapped\Person', 'Gedmo\Fixture\Unmapped\Nameable');
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === 'Gedmo\Fixture\Unmapped\Person') {
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
        } elseif ($className === 'Gedmo\Fixture\Unmapped\Nameable') {
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

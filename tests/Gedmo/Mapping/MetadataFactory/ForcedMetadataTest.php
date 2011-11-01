<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Mapping\Fixture\Unmapped\Timestampable;

/**
* These are mapping tests for tree extension
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @package Gedmo.Mapping
* @link http://www.gediminasm.org
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
class ForcedMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(
            new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($_ENV['annotation_reader'])
        );

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $evm = new \Doctrine\Common\EventManager();
        $this->timestampable = new \Gedmo\Timestampable\TimestampableListener();
        $this->timestampable->setAnnotationReader($_ENV['annotation_reader']);
        $evm->addEventSubscriber($this->timestampable);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    private function prepare()
    {
        $cmf = $this->em->getMetadataFactory();
        $metadata = new ClassMetadata('Mapping\Fixture\Unmapped\Timestampable');
        $id = array();
        $id['fieldName'] = 'id';
        $id['type'] = 'integer';
        $id['nullable'] = false;
        $id['columnName'] = 'id';
        $id['id'] = true;

        $metadata->mapField($id);

        $created = array();
        $created['fieldName'] = 'created';
        $created['type'] = 'datetime';
        $created['nullable'] = false;
        $created['columnName'] = 'created';

        $metadata->mapField($created);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\IdentityGenerator(null));
        $cmf->setMetadataFor('Mapping\Fixture\Unmapped\Timestampable', $metadata);

        // trigger loadClassMetadata event
        $evm = $this->em->getEventManager();
        $eventArgs = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs($metadata, $this->em);
        $evm->dispatchEvent(\Doctrine\ORM\Events::loadClassMetadata, $eventArgs);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('Mapping\Fixture\Unmapped\Timestampable'),
        ));
    }

    /**
     * @test
     */
    public function shouldWork()
    {
        $this->prepare();

        $meta = $this->em->getClassMetadata('Mapping\Fixture\Unmapped\Timestampable');
        // driver falls back to annotation driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            'Mapping\Fixture\Unmapped\Timestampable'
        );
        $this->assertTrue(isset($conf['create']));

        $test = new Timestampable;
        $this->em->persist($test);
        $this->em->flush();

        $id = $this->em
            ->getClassMetadata('Mapping\Fixture\Unmapped\Timestampable')
            ->getReflectionProperty('id')
            ->getValue($test)
        ;
        $this->assertFalse(empty($id));
    }
}

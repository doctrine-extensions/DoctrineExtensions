<?php

namespace Mapping\MetadataFactory;

use Doctrine\Common\EventManager;
use Fixture\Unmapped\Person;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Configuration;
use Gedmo\Timestampable\TimestampableListener;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    const PERSON = 'Fixture\Unmapped\Person';

    protected function setUp()
    {
        parent::setUp();
        $config = new Configuration;
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Mapping\Proxy');
        $config->setMetadataDriverImpl(new CacheTestDriver);

        $this->config = $config;
        $this->conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $this->evm = new EventManager;
        $this->timestampable = new TimestampableListener;
        $this->evm->addEventSubscriber($this->timestampable);
    }

    /**
     * @test
     */
    function shouldTriggerExtensionCaching()
    {
        $cache = new CacheTestMock;
        $this->config->setMetadataCacheImpl($cache);
        $em = EntityManager::create($this->conn, $this->config, $this->evm);
        $em->getClassMetadata(self::PERSON);

        $this->assertTrue(in_array('Fixture\Unmapped\Person$CLASSMETADATA', $cache->calls['fetch']));

        $this->assertTrue(in_array('Fixture\Unmapped\Person\$GEDMO_TIMESTAMPABLE_CLASSMETADATA', $cache->calls['save']));
        $this->assertTrue(in_array('Fixture\Unmapped\Nameable$CLASSMETADATA', $cache->calls['save']));
        $this->assertTrue(in_array('Fixture\Unmapped\Person$CLASSMETADATA', $cache->calls['save']));
    }
}

class CacheTestDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array(CacheTest::PERSON, 'Fixture\Unmapped\Nameable');
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === CacheTest::PERSON) {
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

class CacheTestMock implements Cache
{
    public $calls = array();

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $this->calls['fetch'][] = $id;
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        $this->calls['contains'][] = $id;
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $this->calls['save'][] = $id;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->calls['delete'][] = $id;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return null;
    }
}

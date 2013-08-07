<?php

namespace Mapping\MetadataFactory;

use Doctrine\Common\EventManager;
use Gedmo\Fixture\Unmapped\Person;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Configuration;
use Gedmo\Timestampable\TimestampableListener;
use Doctrine\Common\Cache\Cache;
use Gedmo\TestTool\ObjectManagerTestCase;

class CacheTest extends ObjectManagerTestCase
{
    const PERSON = 'Gedmo\Fixture\Unmapped\Person';

    private $em;
    private $timestampable;
    private $cache;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->timestampable = new TimestampableListener);

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl(new CacheTestDriver);
        $config->setMetadataCacheImpl($this->cache = new CacheTestMock);

        $this->em = $this->createEntityManager($evm, null, $config);
    }

    /**
     * @test
     */
    function shouldTriggerExtensionCaching()
    {
        $this->em->getClassMetadata(self::PERSON);

        $this->assertTrue(isset($this->cache->calls['fetch']));
        $this->assertTrue(in_array('Gedmo\Fixture\Unmapped\Person$CLASSMETADATA', $this->cache->calls['fetch']));

        $this->assertTrue(isset($this->cache->calls['save']));
        $this->assertTrue(in_array('Gedmo\Fixture\Unmapped\Person\$GEDMO_TIMESTAMPABLE_CLASSMETADATA', $this->cache->calls['save']));
        $this->assertTrue(in_array('Gedmo\Fixture\Unmapped\Nameable$CLASSMETADATA', $this->cache->calls['save']));
        $this->assertTrue(in_array('Gedmo\Fixture\Unmapped\Person$CLASSMETADATA', $this->cache->calls['save']));
    }
}

class CacheTestDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array(CacheTest::PERSON, 'Gedmo\Fixture\Unmapped\Nameable');
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

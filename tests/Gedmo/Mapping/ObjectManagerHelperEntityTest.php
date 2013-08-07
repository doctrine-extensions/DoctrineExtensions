<?php

namespace Gedmo\Mapping;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\Unmapped\Address;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class ObjectManagerHelperEntityTest extends ObjectManagerTestCase
{
    const ADDRESS = "Gedmo\Fixture\Unmapped\Address";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl(new ObjectManagerHelperEntityDriver);

        $this->em = $this->createEntityManager($evm, null, $config);
        $this->createSchema($this->em, array(
            self::ADDRESS
        ));

        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldExtractIdentifierFromManagedEntity()
    {
        $test = $this->em->getRepository(self::ADDRESS)->findOneBy(array('city' => 'Kaunas'));

        $this->assertSame(1, OMH::getIdentifier($this->em, $test));
    }

    /**
     * @test
     */
    function shouldManageProxy()
    {
        $this->em->clear();
        $test = $this->em->getReference(self::ADDRESS, array('id' => 1));
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $test);

        $this->assertTrue(OMH::isProxy($test));
        $id = OMH::getIdentifier($this->em, $test, false);
        $this->assertTrue(is_array($id));
        $this->assertCount(1, $id);
        $this->assertArrayHasKey('id', $id);
        $this->assertSame(1, $id['id']);
    }

    /**
     * @test
     */
    function shouldHandleDetachedEntity()
    {
        $test = $this->em->find(self::ADDRESS, array('id' => 1));
        $this->em->clear();

        $this->assertSame(1, OMH::getIdentifier($this->em, $test));
    }

    /**
     * @test
     */
    function shouldAlsoHandleDetachedProxy()
    {
        $this->em->clear();
        $test = $this->em->getReference(self::ADDRESS, array('id' => 1));
        $this->em->clear();

        $this->assertTrue(OMH::isProxy($test));
        $this->assertSame(1, OMH::getIdentifier($this->em, $test));
    }

    private function populate()
    {
        $test = new Address;
        $test->setCity("Kaunas");
        $test->setStreet("Zilvicio g. 17-7");
        $this->em->persist($test);
        $this->em->flush();
    }
}

class ObjectManagerHelperEntityDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array(ObjectManagerHelperEntityTest::ADDRESS);
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === ObjectManagerHelperEntityTest::ADDRESS) {
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

            $street = array(
                'fieldName' => 'street',
                'type' => 'string',
                'nullable' => false,
                'length' => 128,
                'columnName' => 'street'
            );

            $metadata->mapField($street);

            $city = array(
                'fieldName' => 'city',
                'type' => 'string',
                'nullable' => false,
                'length' => 32,
                'columnName' => 'city'
            );

            $metadata->mapField($city);
        }
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }
}

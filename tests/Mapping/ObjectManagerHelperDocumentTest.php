<?php

namespace Mapping;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Fixture\Unmapped\Address;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\MongoDB\Connection;

class ObjectManagerHelperDocumentTest extends BaseTestCaseMongoODM
{
    const ADDRESS = "Fixture\Unmapped\Address";

    protected function setUp()
    {
        parent::setUp();
        $config = new Configuration;
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Mapping\Proxy');
        $config->setMetadataDriverImpl(new ObjectManagerHelperDocumentDriver);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setHydratorNamespace('Mapping\Hydrators');
        $config->setDefaultDB('gedmo_extensions_test');

        $this->dm = DocumentManager::create(new Connection, $config, new EventManager);
        $this->populate();
    }

    /**
     * @test
     */
    function shouldExtractIdentifierFromManagedEntity()
    {
        $test = $this->dm->getRepository(self::ADDRESS)->findOneBy(array('city' => 'Kaunas'));

        $this->assertSame($this->id, OMH::getIdentifier($this->dm, $test));
    }

    /**
     * @test
     */
    function shouldManageProxy()
    {
        $this->dm->clear();
        $test = $this->dm->getReference(self::ADDRESS, $this->id);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $test);

        $this->assertTrue(OMH::isProxy($test));
        $id = OMH::getIdentifier($this->dm, $test, false);
        $this->assertTrue(is_array($id));
        $this->assertCount(1, $id);
        $this->assertArrayHasKey('id', $id);
        $this->assertSame($this->id, $id['id']);
    }

    /**
     * @test
     */
    function shouldHandleDetachedEntity()
    {
        $test = $this->dm->find(self::ADDRESS, $this->id);
        $this->dm->clear();

        $this->assertSame($this->id, OMH::getIdentifier($this->dm, $test));
    }

    /**
     * @test
     */
    function shouldAlsoHandleDetachedProxy()
    {
        $this->dm->clear();
        $test = $this->dm->getReference(self::ADDRESS, $this->id);
        $this->dm->clear();

        $this->assertTrue(OMH::isProxy($test));
        $this->assertSame($this->id, OMH::getIdentifier($this->dm, $test));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ADDRESS
        );
    }

    private function populate()
    {
        $test = new Address;
        $test->setCity("Kaunas");
        $test->setStreet("Zilvicio g. 17-7");
        $this->dm->persist($test);
        $this->dm->flush();

        $this->id = $test->getId();
    }
}

class ObjectManagerHelperDocumentDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array(ObjectManagerHelperDocumentTest::ADDRESS);
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === ObjectManagerHelperDocumentTest::ADDRESS) {
            $id = array();
            $id['fieldName'] = 'id';
            $id['id'] = true;

            $metadata->mapField($id);

            $street = array(
                'fieldName' => 'street',
                'type' => 'string',
                'nullable' => false,
            );

            $metadata->mapField($street);

            $city = array(
                'fieldName' => 'city',
                'type' => 'string',
                'nullable' => false,
            );

            $metadata->mapField($city);
        }
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }
}

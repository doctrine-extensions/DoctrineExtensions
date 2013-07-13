<?php

namespace Mapping;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Fixture\Unmapped\Person;
use Doctrine\ODM\MongoDB\Configuration;
use Fixture\EncoderExtension\EncoderListener;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class EncoderExtensionWithDocumentManagerTest extends BaseTestCaseMongoODM
{
    const PERSON = 'Fixture\Unmapped\Person';

    protected function setUp()
    {
        parent::setUp();

        require_once __DIR__.'/../Fixture/EncoderExtension/Mapping/Annotations.php';
        $evm = new EventManager;
        $evm->addEventSubscriber($this->encoder = new EncoderListener);

        $config = new Configuration;
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Mapping\Proxy');
        $config->setMetadataDriverImpl(new EncoderDocumentCustomDriver);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setHydratorNamespace('Mapping\Hydrators');
        $config->setDefaultDB('mapping_encoders');

        $this->dm = DocumentManager::create(new Connection, $config, $evm);
    }

    /**
     * @test
     */
    function shouldLoadEncoderExtensionMetadata()
    {
        $meta = $this->dm->getClassMetadata(self::PERSON);
        $config = $this->encoder->getConfiguration($this->dm, self::PERSON);
        $this->assertArrayHasKey('encode', $config);
        $this->assertCount(1, $config['encode']);

        $this->assertArrayHasKey('password', $config['encode']);
        $options = $config['encode']['password'];
        $this->assertEquals('sha1', $options['type']);
        $this->assertEquals('guess', $options['secret']);
    }

    /**
     * @test
     */
    function shouldHaveEncodedPassword()
    {
        $user = new Person;
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertSame(sha1('guess' . 'secret'), $user->getPassword());
    }
}

class EncoderDocumentCustomDriver implements MappingDriver
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
            $id['id'] = true;

            $metadata->mapField($id);

            $password = array(
                'fieldName' => 'password',
                'type' => 'string',
                'nullable' => false,
            );

            $metadata->mapField($password);
        } elseif ($className === 'Fixture\Unmapped\Nameable') {
            $metadata->isMappedSuperclass = true;

            $name = array();
            $name['fieldName'] = 'name';
            $name['type'] = 'string';
            $name['nullable'] = false;

            $metadata->mapField($name);
        }
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }
}

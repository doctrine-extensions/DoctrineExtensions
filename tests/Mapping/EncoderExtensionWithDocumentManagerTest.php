<?php

namespace Mapping;

use TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Fixture\Unmapped\Person;
use Fixture\EncoderExtension\EncoderListener;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class EncoderExtensionWithDocumentManagerTest extends ObjectManagerTestCase
{
    const PERSON = 'Fixture\Unmapped\Person';

    private $em;
    private $encoder;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber($this->encoder = new EncoderListener());

        $config = $this->getMongoDBDocumentManagerConfiguration();
        $config->setMetadataDriverImpl(new EncoderExtensionWithDocumentManagerTestDriver());

        $this->dm = $this->createDocumentManager($evm, null, $config);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    public function shouldLoadEncoderExtensionMetadata()
    {
        $meta = $this->dm->getClassMetadata(self::PERSON);
        $exm = $this->encoder->getConfiguration($this->dm, self::PERSON);

        $this->assertCount(1, $fields = $exm->getEncoderFields());
        $this->assertContains('password', $fields);
        $this->assertTrue(is_array($options = $exm->getEncoderOptions('password')));

        $this->assertEquals('sha1', $options['type']);
        $this->assertEquals('guess', $options['secret']);
    }

    /**
     * @test
     */
    public function shouldHaveEncodedPassword()
    {
        $user = new Person();
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertSame(sha1('guess'.'secret'), $user->getPassword());
    }
}

class EncoderExtensionWithDocumentManagerTestDriver implements MappingDriver
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

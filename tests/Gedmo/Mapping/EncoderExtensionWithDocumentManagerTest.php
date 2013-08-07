<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ODM\MongoDB\DocumentManager;
use Gedmo\Fixture\EncoderExtension\EncoderListener;
use Gedmo\Fixture\Unmapped\Person;
use Gedmo\TestTool\ObjectManagerTestCase;

class EncoderExtensionWithDocumentManagerTest extends ObjectManagerTestCase
{
    const PERSON = 'Gedmo\Fixture\Unmapped\Person';

    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var EncoderListener
     */
    private $encoder;

    /**
     * @test
     */
    public function shouldLoadEncoderExtensionMetadata()
    {
        $this->dm->getClassMetadata(self::PERSON);
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
}

class EncoderExtensionWithDocumentManagerTestDriver implements MappingDriver
{
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === 'Gedmo\Fixture\Unmapped\Person') {
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
        } elseif ($className === 'Gedmo\Fixture\Unmapped\Nameable') {
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

    public function getAllClassNames()
    {
        return array('Gedmo\Fixture\Unmapped\Person', 'Gedmo\Fixture\Unmapped\Nameable');
    }
}

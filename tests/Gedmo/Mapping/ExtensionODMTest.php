<?php

namespace Gedmo\Mapping;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Gedmo\Mapping\Mock\Extension\Encoder\EncoderListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Mapping\Fixture\Document\User;

class ExtensionODMTest extends BaseTestCaseMongoODM
{
    const USER = 'Mapping\\Fixture\\Document\\User';

    private $encoderListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->encoderListener = new EncoderListener;
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockDocumentManager($evm);
    }

    public function testExtensionMetadata()
    {
        $meta = $this->dm->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->dm, self::USER);
        $this->assertArrayHasKey('encode', $config);
        $this->assertEquals(2, count($config['encode']));

        $this->assertArrayHasKey('name', $config['encode']);
        $options = $config['encode']['name'];
        $this->assertEquals('sha1', $options['type']);
        $this->assertEquals('xxx', $options['secret']);

        $this->assertArrayHasKey('password', $config['encode']);
        $options = $config['encode']['password'];
        $this->assertEquals('md5', $options['type']);
        $this->assertTrue(empty($options['secret']));
    }

    public function testGeneratedValues()
    {
        $user = new User;
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        $this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed()
    {
        $mappedSubscriberClass = new \ReflectionClass('Gedmo\\Mapping\\MappedEventSubscriber');
        $getEventAdapterMethod = $mappedSubscriberClass->getMethod('getEventAdapter');
        $getEventAdapterMethod->setAccessible(true);

        $loadClassMetadataEventArgs = new LoadClassMetadataEventArgs(
            $this->dm->getClassMetadata(self::USER),
            $this->dm
        );
        $eventAdapter = $getEventAdapterMethod->invoke(
            $this->encoderListener,
            $loadClassMetadataEventArgs
        );
        $this->assertEquals('Gedmo\\Mapping\\Mock\\Extension\\Encoder\\Mapping\\Event\\Adapter\\ODM', get_class($eventAdapter));
    }
}
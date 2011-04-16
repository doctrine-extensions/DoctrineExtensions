<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Mapping\Mock\Extension\Encoder\EncoderListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Mapping\Fixture\User;

class ExtensionORMTest extends BaseTestCaseORM
{
    const USER = 'Mapping\\Fixture\\User';

    private $encoderListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->encoderListener = new EncoderListener;
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testExtensionMetadata()
    {
        $meta = $this->em->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->em, self::USER);
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
        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        $this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed()
    {
        $mappedSubscriberClass = new \ReflectionClass('Gedmo\\Mapping\\MappedEventSubscriber');
        $getEventAdapterMethod = $mappedSubscriberClass->getMethod('getEventAdapter');
        $getEventAdapterMethod->setAccessible(true);

        $loadClassMetadataEventArgs = new LoadClassMetadataEventArgs(
            $this->em->getClassMetadata(self::USER),
            $this->em
        );
        $eventAdapter = $getEventAdapterMethod->invoke(
            $this->encoderListener,
            $loadClassMetadataEventArgs
        );
        $this->assertEquals('Gedmo\\Mapping\\Mock\\Extension\\Encoder\\Mapping\\Event\\Adapter\\ORM', get_class($eventAdapter));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::USER
        );
    }
}
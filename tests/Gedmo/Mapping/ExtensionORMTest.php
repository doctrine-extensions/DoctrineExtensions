<?php

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tests\Mapping\Fixture\User;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\EncoderListener;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class ExtensionORMTest extends BaseTestCaseORM
{
    public const USER = User::class;

    private $encoderListener;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/Mock/Extension/Encoder/Mapping/Annotations.php';
        $evm = new EventManager();
        $this->encoderListener = new EncoderListener();
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testExtensionMetadata()
    {
        $meta = $this->em->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->em, self::USER);
        static::assertArrayHasKey('encode', $config);
        static::assertCount(2, $config['encode']);

        static::assertArrayHasKey('name', $config['encode']);
        $options = $config['encode']['name'];
        static::assertSame('sha1', $options['type']);
        static::assertSame('xxx', $options['secret']);

        static::assertArrayHasKey('password', $config['encode']);
        $options = $config['encode']['password'];
        static::assertSame('md5', $options['type']);
        static::assertEmpty($options['secret']);
    }

    public function testGeneratedValues()
    {
        $user = new User();
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->em->persist($user);
        $this->em->flush();

        static::assertSame('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        static::assertSame('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed()
    {
        $mappedSubscriberClass = new \ReflectionClass(MappedEventSubscriber::class);
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
        static::assertInstanceOf(\Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Event\Adapter\ORM::class, $eventAdapter);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
        ];
    }
}

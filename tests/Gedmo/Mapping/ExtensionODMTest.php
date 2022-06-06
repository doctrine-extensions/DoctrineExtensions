<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tests\Mapping\Fixture\Document\User;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\EncoderListener;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

final class ExtensionODMTest extends BaseTestCaseMongoODM
{
    public const USER = User::class;

    /**
     * @var EncoderListener
     */
    private $encoderListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->encoderListener = new EncoderListener();
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockDocumentManager($evm);
    }

    public function testExtensionMetadata(): void
    {
        $meta = $this->dm->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->dm, self::USER);
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

    public function testGeneratedValues(): void
    {
        $user = new User();
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->dm->persist($user);
        $this->dm->flush();

        static::assertSame('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        static::assertSame('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed(): void
    {
        $mappedSubscriberClass = new \ReflectionClass(MappedEventSubscriber::class);
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
        static::assertInstanceOf(\Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Event\Adapter\ODM::class, $eventAdapter);
    }
}

<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\Sluggable\Prefix;
use Gedmo\Fixture\Sluggable\Suffix;
use Gedmo\TestTool\ObjectManagerTestCase;

class SluggablePrefixSuffixTest extends ObjectManagerTestCase
{
    const PREFIX = 'Gedmo\Fixture\Sluggable\Prefix';
    const SUFFIX = 'Gedmo\Fixture\Sluggable\Suffix';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::PREFIX,
            self::SUFFIX,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldHandlePrefix()
    {
        $foo = new Prefix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertSame('test-bar', $foo->getSlug());
    }

    /**
     * @test
     */
    public function shouldHandleSuffix()
    {
        $foo = new Suffix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('foo.test', $foo->getSlug());
    }
}

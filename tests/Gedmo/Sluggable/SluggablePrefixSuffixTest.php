<?php

/**
 * Created by Dirk Luijk (dirk@luijkwebcreations.nl)
 * 2013
 */
namespace Gedmo\Sluggable;


use Doctrine\Common\EventManager;
use Gedmo\Fixture\Sluggable\Prefix;
use Gedmo\Fixture\Sluggable\Suffix;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Sluggable\SluggableListener;

class SluggablePrefixSuffixTest extends ObjectManagerTestCase
{
    const PREFIX = 'Gedmo\Fixture\Sluggable\Prefix';
    const SUFFIX = 'Gedmo\Fixture\Sluggable\Suffix';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

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
    function shouldHandlePrefix()
    {
        $foo = new Prefix();
        $foo->setTitle('Bar');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertSame('test-bar', $foo->getSlug());
    }

    /**
     * @test
     */
    function shouldHandleSuffix()
    {
        $foo = new Suffix();
        $foo->setTitle('Bar');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertSame('bar.test', $foo->getSlug());
    }
}

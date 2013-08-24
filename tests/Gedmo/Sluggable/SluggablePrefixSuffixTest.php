<?php
/**
 * Created by Dirk Luijk (dirk@luijkwebcreations.nl)
 * 2013
 */

namespace Gedmo\Sluggable;


use Doctrine\Common\EventManager;
use Sluggable\Fixture\Prefix;
use Sluggable\Fixture\Suffix;
use Tool\BaseTestCaseORM;

class SluggablePrefixSuffixTest extends BaseTestCaseORM {

    const PREFIX = 'Sluggable\\Fixture\\Prefix';
    const SUFFIX = 'Sluggable\\Fixture\\Suffix';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function testPrefix()
    {
        $foo = new Prefix();
        $foo->setTitle('Bar');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('test-bar', $foo->getSlug());
    }

    /**
     * @test
     */
    function testSuffix()
    {
        $foo = new Suffix();
        $foo->setTitle('Bar');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('bar.test', $foo->getSlug());
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array
     */
    protected function getUsedEntityFixtures()
    {
        return array(
            self::SUFFIX,
            self::PREFIX,
        );
    }
}
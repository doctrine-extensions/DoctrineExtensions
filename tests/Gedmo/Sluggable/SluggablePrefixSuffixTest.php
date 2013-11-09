<?php

namespace Gedmo\Sluggable;


use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Sluggable\Fixture\Prefix;
use Sluggable\Fixture\PrefixWithTreeHandler;
use Sluggable\Fixture\Suffix;
use Sluggable\Fixture\SuffixWithTreeHandler;
use Tool\BaseTestCaseORM;

class SluggablePrefixSuffixTest extends BaseTestCaseORM {

    const PREFIX = 'Sluggable\\Fixture\\Prefix';
    const SUFFIX = 'Sluggable\\Fixture\\Suffix';
    const SUFFIX_TREE = 'Sluggable\\Fixture\\SuffixWithTreeHandler';
    const PREFIX_TREE = 'Sluggable\\Fixture\\PrefixWithTreeHandler';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function testPrefix()
    {
        $foo = new Prefix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('test-foo', $foo->getSlug());
    }

    /**
     * @test
     */
    function testSuffix()
    {
        $foo = new Suffix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('foo.test', $foo->getSlug());
    }

    /**
     * @test
     */
    function testNoDuplicateSuffixes()
    {
        $foo = new SuffixWithTreeHandler();
        $foo->setTitle('Foo');

        $bar = new SuffixWithTreeHandler();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new SuffixWithTreeHandler();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);
        $this->em->flush();

        $this->assertEquals('foo.test/bar.test/baz.test', $baz->getSlug());
    }

    /**
     * @test
     */
    function testNoDuplicatePrefixes()
    {
        $foo = new PrefixWithTreeHandler();
        $foo->setTitle('Foo');

        $bar = new PrefixWithTreeHandler();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new PrefixWithTreeHandler();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);
        $this->em->flush();

        $this->assertEquals('test.foo/test.bar/test.baz', $baz->getSlug());
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
            self::SUFFIX_TREE,
            self::PREFIX_TREE,
        );
    }
}
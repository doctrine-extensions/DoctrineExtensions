<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Prefix;
use Gedmo\Tests\Sluggable\Fixture\PrefixWithTreeHandler;
use Gedmo\Tests\Sluggable\Fixture\Suffix;
use Gedmo\Tests\Sluggable\Fixture\SuffixWithTreeHandler;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tree\TreeListener;

class SluggablePrefixSuffixTest extends BaseTestCaseORM
{
    public const PREFIX = 'Gedmo\\Tests\\Sluggable\\Fixture\\Prefix';
    public const SUFFIX = 'Gedmo\\Tests\\Sluggable\\Fixture\\Suffix';
    public const SUFFIX_TREE = 'Gedmo\\Tests\\Sluggable\\Fixture\\SuffixWithTreeHandler';
    public const PREFIX_TREE = 'Gedmo\\Tests\\Sluggable\\Fixture\\PrefixWithTreeHandler';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testPrefix()
    {
        $foo = new Prefix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('test-foo', $foo->getSlug());
    }

    public function testSuffix()
    {
        $foo = new Suffix();
        $foo->setTitle('Foo');
        $this->em->persist($foo);
        $this->em->flush();

        $this->assertEquals('foo.test', $foo->getSlug());
    }

    public function testNoDuplicateSuffixes()
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

    public function testNoDuplicatePrefixes()
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
        return [
            self::SUFFIX,
            self::PREFIX,
            self::SUFFIX_TREE,
            self::PREFIX_TREE,
        ];
    }
}

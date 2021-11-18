<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Handler\TreeSlugPrefixSuffix;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tree\TreeListener;

final class TreeSlugHandlerPrefixSuffixTest extends BaseTestCaseORM
{
    public const TARGET = TreeSlugPrefixSuffix::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testPrefixSuffix()
    {
        $foo = new TreeSlugPrefixSuffix();
        $foo->setTitle('Foo');

        $bar = new TreeSlugPrefixSuffix();
        $bar->setTitle('Bar');
        $bar->setParent($foo);

        $baz = new TreeSlugPrefixSuffix();
        $baz->setTitle('Baz');
        $baz->setParent($bar);

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);

        $this->em->flush();

        static::assertSame('prefix.foo/bar/baz.suffix', $baz->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Handlers;

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

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testPrefixSuffix(): void
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}

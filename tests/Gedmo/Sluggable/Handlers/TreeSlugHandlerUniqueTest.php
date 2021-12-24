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
use Gedmo\Tests\Sluggable\Fixture\Handler\TreeSlug;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tree\TreeListener;

final class TreeSlugHandlerUniqueTest extends BaseTestCaseORM
{
    public const TARGET = TreeSlug::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testUniqueRoot(): void
    {
        $foo1 = new TreeSlug();
        $foo1->setTitle('Foo');

        $foo2 = new TreeSlug();
        $foo2->setTitle('Foo');

        $this->em->persist($foo1);
        $this->em->persist($foo2);

        $this->em->flush();

        static::assertSame('foo', $foo1->getSlug());
        static::assertSame('foo-1', $foo2->getSlug());
    }

    public function testUniqueLeaf(): void
    {
        $root = new TreeSlug();
        $root->setTitle('root');

        $foo1 = new TreeSlug();
        $foo1->setTitle('Foo');
        $foo1->setParent($root);

        $foo2 = new TreeSlug();
        $foo2->setTitle('Foo');
        $foo2->setParent($root);

        $this->em->persist($root);
        $this->em->persist($foo1);
        $this->em->persist($foo2);

        $this->em->flush();

        static::assertSame('root/foo', $foo1->getSlug());
        static::assertSame('root/foo-1', $foo2->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}

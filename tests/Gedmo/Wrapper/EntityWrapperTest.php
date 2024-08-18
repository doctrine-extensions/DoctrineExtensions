<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Wrapper;

use Doctrine\Common\EventManager;
use Doctrine\Persistence\Proxy;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Wrapper\Fixture\Entity\Article;
use Gedmo\Tests\Wrapper\Fixture\Entity\Composite;
use Gedmo\Tests\Wrapper\Fixture\Entity\CompositeRelation;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Entity wrapper tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class EntityWrapperTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getDefaultMockSqliteEntityManager(new EventManager());
        $this->populate();
    }

    public function testManaged(): void
    {
        $test = $this->em->find(Article::class, ['id' => 1]);
        static::assertInstanceOf(Article::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
        $wrapped->setPropertyValue('title', 'changed');
        static::assertSame('changed', $wrapped->getPropertyValue('title'));

        static::assertTrue($wrapped->hasValidIdentifier());
    }

    public function testProxy(): void
    {
        $this->em->clear();
        $test = $this->em->getReference(Article::class, ['id' => 1]);
        static::assertInstanceOf(Proxy::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        static::assertIsArray($id);
        static::assertCount(1, $id);
        static::assertArrayHasKey('id', $id);
        static::assertSame(1, $id['id']);

        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testComposite(): void
    {
        $test = $this->em->getReference(Composite::class, ['one' => 1, 'two' => 2]);
        static::assertInstanceOf(Composite::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        static::assertIsArray($id);
        static::assertCount(2, $id);
        static::assertArrayHasKey('one', $id);
        static::assertArrayHasKey('two', $id);
        static::assertSame(1, $id['one']);
        static::assertSame(2, $id['two']);

        $id = $wrapped->getIdentifier(false, true);
        static::assertIsString($id);
        static::assertSame('1 2', $id);

        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testCompositeRelation(): void
    {
        $art1 = $this->em->getReference(Article::class, ['id' => 1]);
        $test = $this->em->getReference(CompositeRelation::class, ['article' => $art1->getId(), 'status' => 2]);
        static::assertInstanceOf(CompositeRelation::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        static::assertIsArray($id);
        static::assertCount(2, $id);
        static::assertArrayHasKey('article', $id);
        static::assertArrayHasKey('status', $id);

        $id = $wrapped->getIdentifier(false, true);
        static::assertIsString($id);
        static::assertSame('1 2', $id);

        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedEntity(): void
    {
        $test = $this->em->find(Article::class, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy(): void
    {
        $test = $this->em->getReference(Article::class, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedCompositeRelation(): void
    {
        $test = $this->em->getReference(CompositeRelation::class, ['article' => 1, 'status' => 2]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame('1 2', $wrapped->getIdentifier(false, true));
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testCompositeRelationProxy(): void
    {
        $this->em->clear();
        $art1 = $this->em->getReference(Article::class, ['id' => 1]);
        $test = $this->em->getReference(CompositeRelation::class, ['article' => $art1->getId(), 'status' => 2]);
        static::assertInstanceOf(Proxy::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame('1 2', $wrapped->getIdentifier(false, true));
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testSomeFunctions(): void
    {
        $test = new Article();
        $wrapped = new EntityWrapper($test, $this->em);

        $test->setTitle('test');
        static::assertSame('test', $wrapped->getPropertyValue('title'));

        static::assertFalse($wrapped->hasValidIdentifier());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Article::class,
            Composite::class,
            CompositeRelation::class,
        ];
    }

    private function populate(): void
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $composite = new Composite(1, 2);
        $composite->setTitle('test');
        $this->em->persist($composite);
        $compositeRelation = new CompositeRelation($article, 2);
        $compositeRelation->setTitle('test');
        $this->em->persist($compositeRelation);
        $this->em->flush();
    }
}

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
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Wrapper\Fixture\Entity\Article;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * Entity wrapper tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class EntityWrapperTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getDefaultMockSqliteEntityManager(new EventManager());
        $this->populate();
    }

    public function testManaged(): void
    {
        $test = $this->em->find(self::ARTICLE, ['id' => 1]);
        static::assertInstanceOf(self::ARTICLE, $test);
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
        $test = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        static::assertInstanceOf(Proxy::class, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        static::assertIsArray($id);
        static::assertCount(1, $id);
        static::assertArrayHasKey('id', $id);
        static::assertSame(1, $id['id']);

        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedEntity(): void
    {
        $test = $this->em->find(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy(): void
    {
        $test = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
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
            self::ARTICLE,
        ];
    }

    private function populate(): void
    {
        $test = new Article();
        $test->setTitle('test');
        $this->em->persist($test);
        $this->em->flush();
    }
}

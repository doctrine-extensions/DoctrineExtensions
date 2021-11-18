<?php

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
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class EntityWrapperTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMockSqliteEntityManager(new EventManager());
        $this->populate();
    }

    public function testManaged()
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

    public function testProxy()
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

    public function testDetachedEntity()
    {
        $test = $this->em->find(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy()
    {
        $test = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        static::assertSame(1, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testSomeFunctions()
    {
        $test = new Article();
        $wrapped = new EntityWrapper($test, $this->em);

        $wrapped->populate(['title' => 'test']);
        static::assertSame('test', $wrapped->getPropertyValue('title'));

        static::assertFalse($wrapped->hasValidIdentifier());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
    }

    private function populate()
    {
        $test = new Article();
        $test->setTitle('test');
        $this->em->persist($test);
        $this->em->flush();
    }
}

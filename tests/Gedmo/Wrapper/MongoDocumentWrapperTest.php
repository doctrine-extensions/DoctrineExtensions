<?php

namespace Gedmo\Tests\Wrapper;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Wrapper\Fixture\Document\Article;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * Mongo Document wrapper tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class MongoDocumentWrapperTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMockDocumentManager(new EventManager());
        $this->populate();
    }

    public function testManaged()
    {
        $test = $this->dm->find(self::ARTICLE, $this->articleId);
        static::assertInstanceOf(self::ARTICLE, $test);
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        static::assertSame($this->articleId, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
        $wrapped->setPropertyValue('title', 'changed');
        static::assertSame('changed', $wrapped->getPropertyValue('title'));

        static::assertTrue($wrapped->hasValidIdentifier());
    }

    public function testProxy()
    {
        $this->dm->clear();
        $test = $this->dm->getReference(self::ARTICLE, $this->articleId);
        static::assertStringStartsWith('Proxy', get_class($test));
        static::assertInstanceOf(self::ARTICLE, $test);
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $id = $wrapped->getIdentifier(false);
        static::assertSame($this->articleId, $id);

        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedEntity()
    {
        $test = $this->dm->find(self::ARTICLE, $this->articleId);
        $this->dm->clear();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        static::assertSame($this->articleId, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy()
    {
        $test = $this->dm->getReference(self::ARTICLE, $this->articleId);
        $this->dm->clear();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        static::assertSame($this->articleId, $wrapped->getIdentifier());
        static::assertSame('test', $wrapped->getPropertyValue('title'));
    }

    public function testSomeFunctions()
    {
        $test = new Article();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $wrapped->populate(['title' => 'test']);
        static::assertSame('test', $wrapped->getPropertyValue('title'));

        static::assertFalse($wrapped->hasValidIdentifier());
    }

    private function populate()
    {
        $test = new Article();
        $test->setTitle('test');
        $this->dm->persist($test);
        $this->dm->flush();
        $this->articleId = $test->getId();
    }
}

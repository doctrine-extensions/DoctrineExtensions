<?php

namespace Gedmo\Tests\ReferenceIntegrity;

use Doctrine\Common\EventManager;
use Gedmo\Exception\ReferenceIntegrityStrictException;
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyRestrict\Article;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyRestrict\Type;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for the ReferenceIntegrity extension
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ReferenceIntegrityDocumentTest extends BaseTestCaseMongoODM
{
    public const TYPE_ONE_NULLIFY_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneNullify\Type::class;
    public const ARTICLE_ONE_NULLIFY_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneNullify\Article::class;

    public const TYPE_MANY_NULLIFY_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify\Type::class;
    public const ARTICLE_MANY_NULLIFY_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify\Article::class;

    public const TYPE_ONE_PULL_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OnePull\Type::class;
    public const ARTICLE_ONE_PULL_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OnePull\Article::class;

    public const TYPE_MANY_PULL_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyPull\Type::class;
    public const ARTICLE_MANY_PULL_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyPull\Article::class;

    public const TYPE_ONE_RESTRICT_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneRestrict\Type::class;
    public const ARTICLE_ONE_RESTRICT_CLASS = \Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneRestrict\Article::class;

    public const TYPE_MANY_RESTRICT_CLASS = Type::class;
    public const ARTICLE_MANY_RESTRICT_CLASS = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new ReferenceIntegrityListener());

        $this->dm = $this->getMockDocumentManager($evm, $this->getMockAnnotatedConfig());

        $this->populateOneNullify();
        $this->populateManyNullify();

        $this->populateOnePull();
        $this->populateManyPull();

        $this->populateOneRestrict();
        $this->populateManyRestrict();
    }

    public function testOneNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Type']);
        static::assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Article']);

        static::assertNull($article->getType());

        $this->dm->clear();
    }

    public function testManyNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Type']);
        static::assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Article']);

        static::assertNull($article->getType());

        $this->dm->clear();
    }

    public function testOnePull()
    {
        $type1 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 1']);
        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 2']);

        static::assertNotNull($type1);
        static::assertIsObject($type1);

        static::assertNotNull($type2);
        static::assertIsObject($type2);

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 2']);
        static::assertNull($type2);

        $article = $this->dm->getRepository(self::ARTICLE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Article']);

        $types = $article->getTypes();
        static::assertCount(1, $types);
        static::assertSame('One Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    public function testManyPull()
    {
        $type1 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 1']);
        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 2']);

        static::assertNotNull($type1);
        static::assertIsObject($type1);

        static::assertNotNull($type2);
        static::assertIsObject($type2);

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(self::TYPE_MANY_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 2']);
        static::assertNull($type2);

        $article = $this->dm->getRepository(self::ARTICLE_MANY_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Article']);

        $types = $article->getTypes();
        static::assertCount(1, $types);
        static::assertSame('Many Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    public function testOneRestrict()
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(self::TYPE_ONE_RESTRICT_CLASS)
            ->findOneBy(['title' => 'One Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    public function testManyRestrict()
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(self::TYPE_MANY_RESTRICT_CLASS)
            ->findOneBy(['title' => 'Many Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    private function populateOneNullify()
    {
        $typeClass = self::TYPE_ONE_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Nullify Type');

        $articleClass = self::ARTICLE_ONE_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyNullify()
    {
        $typeClass = self::TYPE_MANY_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Nullify Type');

        $articleClass = self::ARTICLE_MANY_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOnePull()
    {
        $typeClass = self::TYPE_ONE_PULL_CLASS;
        $type1 = new $typeClass();
        $type1->setTitle('One Pull Type 1');

        $type2 = new $typeClass();
        $type2->setTitle('One Pull Type 2');

        $articleClass = self::ARTICLE_ONE_PULL_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Pull Article');
        $article->addType($type1);
        $article->addType($type2);

        $this->dm->persist($article);
        $this->dm->persist($type1);
        $this->dm->persist($type2);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyPull()
    {
        $typeClass = self::TYPE_MANY_PULL_CLASS;
        $type1 = new $typeClass();
        $type1->setTitle('Many Pull Type 1');

        $type2 = new $typeClass();
        $type2->setTitle('Many Pull Type 2');

        $articleClass = self::ARTICLE_MANY_PULL_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Pull Article');
        $article->addType($type1);
        $article->addType($type2);

        $this->dm->persist($article);
        $this->dm->persist($type1);
        $this->dm->persist($type2);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOneRestrict()
    {
        $typeClass = self::TYPE_ONE_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Restrict Type');

        $articleClass = self::ARTICLE_ONE_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyRestrict()
    {
        $typeClass = self::TYPE_MANY_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Restrict Type');

        $articleClass = self::ARTICLE_MANY_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }
}

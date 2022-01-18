<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
 */
final class ReferenceIntegrityDocumentTest extends BaseTestCaseMongoODM
{
    public const TYPE_ONE_NULLIFY_CLASS = Fixture\Document\OneNullify\Type::class;
    public const ARTICLE_ONE_NULLIFY_CLASS = Fixture\Document\OneNullify\Article::class;

    public const TYPE_MANY_NULLIFY_CLASS = Fixture\Document\ManyNullify\Type::class;
    public const ARTICLE_MANY_NULLIFY_CLASS = Fixture\Document\ManyNullify\Article::class;

    public const TYPE_ONE_PULL_CLASS = Fixture\Document\OnePull\Type::class;
    public const ARTICLE_ONE_PULL_CLASS = Fixture\Document\OnePull\Article::class;

    public const TYPE_MANY_PULL_CLASS = Fixture\Document\ManyPull\Type::class;
    public const ARTICLE_MANY_PULL_CLASS = Fixture\Document\ManyPull\Article::class;

    public const TYPE_ONE_RESTRICT_CLASS = Fixture\Document\OneRestrict\Type::class;
    public const ARTICLE_ONE_RESTRICT_CLASS = Fixture\Document\OneRestrict\Article::class;

    public const TYPE_MANY_RESTRICT_CLASS = Type::class;
    public const ARTICLE_MANY_RESTRICT_CLASS = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new ReferenceIntegrityListener());

        $this->dm = $this->getDefaultDocumentManager($evm);

        $this->populateOneNullify();
        $this->populateManyNullify();

        $this->populateOnePull();
        $this->populateManyPull();

        $this->populateOneRestrict();
        $this->populateManyRestrict();
    }

    public function testOneNullify(): void
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

    public function testManyNullify(): void
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

    public function testOnePull(): void
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

    public function testManyPull(): void
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

    public function testOneRestrict(): void
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(self::TYPE_ONE_RESTRICT_CLASS)
            ->findOneBy(['title' => 'One Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    public function testManyRestrict(): void
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(self::TYPE_MANY_RESTRICT_CLASS)
            ->findOneBy(['title' => 'Many Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    private function populateOneNullify(): void
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

    private function populateManyNullify(): void
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

    private function populateOnePull(): void
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

    private function populateManyPull(): void
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

    private function populateOneRestrict(): void
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

    private function populateManyRestrict(): void
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

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
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify\Article as ArticleManyNullify;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify\Type as TypeManyNullify;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyPull\Article as ArticleManyPull;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyPull\Type as TypeManyPull;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyRestrict\Article;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyRestrict\Type;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneNullify\Article as ArticleOneNullify;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneNullify\Type as TypeOneNullify;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OnePull\Article as ArticleOnePull;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OnePull\Type as TypeOnePull;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneRestrict\Article as ArticleOneRestrict;
use Gedmo\Tests\ReferenceIntegrity\Fixture\Document\OneRestrict\Type as TypeOneRestrict;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for the ReferenceIntegrity extension
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 */
final class ReferenceIntegrityDocumentTest extends BaseTestCaseMongoODM
{
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
        $type = $this->dm->getRepository(TypeOneNullify::class)
            ->findOneBy(['title' => 'One Nullify Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(TypeOneNullify::class)
            ->findOneBy(['title' => 'One Nullify Type']);
        static::assertNull($type);

        $article = $this->dm->getRepository(ArticleOneNullify::class)
            ->findOneBy(['title' => 'One Nullify Article']);

        static::assertNull($article->getType());

        $this->dm->clear();
    }

    public function testManyNullify(): void
    {
        $type = $this->dm->getRepository(TypeManyNullify::class)
            ->findOneBy(['title' => 'Many Nullify Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(TypeManyNullify::class)
            ->findOneBy(['title' => 'Many Nullify Type']);
        static::assertNull($type);

        $article = $this->dm->getRepository(ArticleManyNullify::class)
            ->findOneBy(['title' => 'Many Nullify Article']);

        static::assertNull($article->getType());

        $this->dm->clear();
    }

    public function testOnePull(): void
    {
        $type1 = $this->dm->getRepository(TypeOnePull::class)
            ->findOneBy(['title' => 'One Pull Type 1']);
        $type2 = $this->dm->getRepository(TypeOnePull::class)
            ->findOneBy(['title' => 'One Pull Type 2']);

        static::assertNotNull($type1);
        static::assertIsObject($type1);

        static::assertNotNull($type2);
        static::assertIsObject($type2);

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(TypeOnePull::class)
            ->findOneBy(['title' => 'One Pull Type 2']);
        static::assertNull($type2);

        $article = $this->dm->getRepository(ArticleOnePull::class)
            ->findOneBy(['title' => 'One Pull Article']);

        $types = $article->getTypes();
        static::assertCount(1, $types);
        static::assertSame('One Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    public function testManyPull(): void
    {
        $type1 = $this->dm->getRepository(TypeOnePull::class)
            ->findOneBy(['title' => 'Many Pull Type 1']);
        $type2 = $this->dm->getRepository(TypeOnePull::class)
            ->findOneBy(['title' => 'Many Pull Type 2']);

        static::assertNotNull($type1);
        static::assertIsObject($type1);

        static::assertNotNull($type2);
        static::assertIsObject($type2);

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(TypeManyPull::class)
            ->findOneBy(['title' => 'Many Pull Type 2']);
        static::assertNull($type2);

        $article = $this->dm->getRepository(ArticleManyPull::class)
            ->findOneBy(['title' => 'Many Pull Article']);

        $types = $article->getTypes();
        static::assertCount(1, $types);
        static::assertSame('Many Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    public function testOneRestrict(): void
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(TypeOneRestrict::class)
            ->findOneBy(['title' => 'One Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    public function testManyRestrict(): void
    {
        $this->expectException(ReferenceIntegrityStrictException::class);
        $type = $this->dm->getRepository(Type::class)
            ->findOneBy(['title' => 'Many Restrict Type']);

        static::assertNotNull($type);
        static::assertIsObject($type);

        $this->dm->remove($type);
        $this->dm->flush();
    }

    private function populateOneNullify(): void
    {
        $type = new TypeOneNullify();
        $type->setTitle('One Nullify Type');

        $article = new ArticleOneNullify();
        $article->setTitle('One Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyNullify(): void
    {
        $type = new TypeManyNullify();
        $type->setTitle('Many Nullify Type');

        $article = new ArticleManyNullify();
        $article->setTitle('Many Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOnePull(): void
    {
        $type1 = new TypeOnePull();
        $type1->setTitle('One Pull Type 1');

        $type2 = new TypeOnePull();
        $type2->setTitle('One Pull Type 2');

        $article = new ArticleOnePull();
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
        $type1 = new TypeManyPull();
        $type1->setTitle('Many Pull Type 1');

        $type2 = new TypeManyPull();
        $type2->setTitle('Many Pull Type 2');

        $article = new ArticleManyPull();
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
        $type = new TypeOneRestrict();
        $type->setTitle('One Restrict Type');

        $article = new ArticleOneRestrict();
        $article->setTitle('One Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyRestrict(): void
    {
        $type = new Type();
        $type->setTitle('Many Restrict Type');

        $article = new Article();
        $article->setTitle('Many Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }
}

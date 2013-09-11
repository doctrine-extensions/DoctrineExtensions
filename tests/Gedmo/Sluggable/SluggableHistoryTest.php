<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableHistoryTest extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\ArticleWithHistory';
    const ARTICLE_MANY = 'Sluggable\\Fixture\\TransArticleManySlugWithHistory';
	const HISTORY = 'Sluggable\\Fixture\\History';

    private $articleId;
    private $transArticleId;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    function shouldStoreSlugHistoryOnUpdate()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();

		$historyRepository = $this->em->getRepository(self::HISTORY);
		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE, $article);
		$this->assertSame($this->articleId, $article->getId());

        $article->setTitle('the title updated second time');
        $this->em->persist($article);
        $this->em->flush();

		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE, $article);
		$this->assertSame($this->articleId, $article->getId());

		$article = $historyRepository->findOneBySlug('the-title-updated-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE, $article);
		$this->assertSame($this->articleId, $article->getId());
    }

    /**
     * @test
     */
    function shouldDeleteHistoryOnEntityDelete()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();

		// Remove entity
		$this->em->remove($article);
        $this->em->flush();

		// Test that slug history for the entity were deleted
		$historyRepository = $this->em->getRepository(self::HISTORY);
		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNull($article);
    }

    /**
     * @test
     */
    function shouldStoreSlugHistoryOnUpdateWithManySlugs()
    {
        $article = $this->em->find(self::ARTICLE_MANY, $this->articleId);
        $article->setTitle('the title updated');
		$article->setUniqueTitle('the unique title updated');
        $this->em->persist($article);
        $this->em->flush();

		$historyRepository = $this->em->getRepository(self::HISTORY);
		// By slug
		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());
		// By uniqueSlug
		$article = $historyRepository->findOneByUniqueSlug('the-unique-title');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());

        $article->setTitle('the title updated second time');
		$article->setUniqueTitle('the unique title updated second time');
        $this->em->persist($article);
        $this->em->flush();

		// The old old slug
		// By slug
		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());
		// By uniqueSlug
		$article = $historyRepository->findOneByUniqueSlug('the-unique-title');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());

		// By slug
		$article = $historyRepository->findOneBySlug('the-title-updated-my-code');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());
		// By uniqueSlug
		$article = $historyRepository->findOneByUniqueSlug('the-unique-title-updated');
		$this->assertNotNull($article);
		$this->assertInstanceOf(self::ARTICLE_MANY, $article);
		$this->assertSame($this->articleId, $article->getId());
    }

    /**
     * @test
     */
    function shouldDeleteHistoryOnEntityDeleteWithManySlugs()
    {
        $article = $this->em->find(self::ARTICLE_MANY, $this->articleId);
        $article->setTitle('the title updated');
		$article->setUniqueTitle('the unique title updated');
        $this->em->persist($article);
        $this->em->flush();

		// Remove entity
		$this->em->remove($article);
        $this->em->flush();

		$historyRepository = $this->em->getRepository(self::HISTORY);
		$article = $historyRepository->findOneBySlug('the-title-my-code');
		$this->assertNull($article);
		$article = $historyRepository->findOneByUniqueSlug('the-unique-title');
		$this->assertNull($article);
    }

	protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::ARTICLE_MANY,
			self::HISTORY
        );
    }

    private function populate()
    {
		$class = self::ARTICLE;
        $article = new $class();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();

		$class = self::ARTICLE_MANY;
        $article = new $class();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->transArticleId = $article->getId();
    }
}

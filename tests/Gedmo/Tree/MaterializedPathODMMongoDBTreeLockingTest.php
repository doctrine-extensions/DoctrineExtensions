<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Exception\TreeLockingException;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Tree\Fixture\Document\Article;
use Gedmo\Tests\Tree\Fixture\Mock\TreeListenerMock;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathODMMongoDBTreeLockingTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var TreeListenerMock
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListenerMock();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultDocumentManager($evm);

        $meta = $this->dm->getClassMetadata(self::ARTICLE);
        $this->config = $this->listener->getConfiguration($this->dm, $meta->getName());
    }

    public function testModifyingANodeWhileItsTreeIsLockedShouldThrowAnException(): void
    {
        // By default, TreeListenerMock disables the release of the locks
        // for testing purposes
        $this->expectException(TreeLockingException::class);

        $article = $this->createArticle();
        $article->setTitle('1');
        $article2 = $this->createArticle();
        $article2->setTitle('2');
        $article2->setParent($article);

        $this->dm->persist($article);
        $this->dm->persist($article2);
        $this->dm->flush();

        $this->dm->refresh($article);
        $this->dm->refresh($article2);

        $article2->setTitle('New title');
        $this->dm->flush();
    }

    public function testModifyingANodeWhileItsTreeIsNotLockedShouldNotThrowException(): void
    {
        static::markTestSkipped('the locking test is failing after removal of scheduleExtraUpdate');
        $article = $this->createArticle();
        $article->setTitle('1');
        $article2 = $this->createArticle();
        $article2->setTitle('2');
        $article2->setParent($article);

        // These tree will be locked after flush, simulating concurrency
        $this->dm->persist($article);
        $this->dm->persist($article2);
        $this->dm->flush();
        $this->dm->clear();

        // These one will release the lock as normal
        $this->listener->setReleaseLocks(true);

        $article3 = $this->createArticle();
        $article3->setTitle('3');

        $this->dm->persist($article3);
        $this->dm->flush();

        // This should NOT throw an exception
        $article3->setTitle('New title');
        $this->dm->flush();

        // But this should throw it, because the root of its tree ($article) is still locked
        $this->expectException(TreeLockingException::class);

        $repo = $this->dm->getRepository(self::ARTICLE);
        $article2 = $repo->findOneBy(['title' => '2']);
        $article2->setTitle('New title 2');

        $this->dm->flush();
    }

    public function createArticle(): Article
    {
        $class = self::ARTICLE;

        return new $class();
    }
}

<?php

namespace Tree\MaterializedPath\Document;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Tree\MaterializedPath\Mock\TreeListenerMock;

class LockingTest extends ObjectManagerTestCase
{
    const ARTICLE = "Fixture\Tree\MaterializedPath\Document\Article";

    private $config, $dm, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListenerMock);

        $this->dm = $this->createDocumentManager($evm);

        $meta = $this->dm->getClassMetadata(self::ARTICLE);
        $this->config = $this->listener->getConfiguration($this->dm, $meta->name)->getMapping();
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    public function modifyingANodeWhileItsTreeIsLockedShouldThrowAnException()
    {
        // By default, TreeListenerMock disables the release of the locks
        // for testing purposes
        $this->setExpectedException('Gedmo\Exception\TreeLockingException');

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

    /**
     * @test
     */
    public function modifyingANodeWhileItsTreeIsNotLockedShouldNotThrowException()
    {
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
        $this->setExpectedException('Gedmo\Exception\TreeLockingException');

        $repo = $this->dm->getRepository(self::ARTICLE);
        $article2 = $repo->findOneByTitle('2');
        $article2->setTitle('New title 2');

        $this->dm->flush();
    }

    public function createArticle()
    {
        $class = self::ARTICLE;
        return new $class;
    }
}

<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\RootCategory;
use Tree\Fixture\Mock\TreeListenerMock;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathODMMongoDBTreeLockingTest extends BaseTestCaseMongoODM
{
    const ARTICLE = "Tree\\Fixture\\Document\\Article";

    protected $config;
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new TreeListenerMock;

        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener);

        $this->getMockDocumentManager($evm);

        $meta = $this->dm->getClassMetadata(self::ARTICLE);
        $this->config = $this->listener->getConfiguration($this->dm, $meta->name);
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

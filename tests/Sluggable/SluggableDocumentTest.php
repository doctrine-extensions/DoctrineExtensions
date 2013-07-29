<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Fixture\Sluggable\Document\Article;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableDocumentTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Fixture\Sluggable\Document\Article';

    /**
     * @var DocumentManager
     */
    private $dm;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->dm = $this->createDocumentManager($evm);
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    public function testSlugGeneration()
    {
        // test insert
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('My Title');

        $this->assertEquals('my-title-the-code', $article->getSlug());

        // test update
        $article->setTitle('New Title');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneByTitle('New Title');
        $this->assertEquals('new-title-the-code', $article->getSlug());
    }

    public function testUniqueSlugGeneration()
    {
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('My Title');
            $article->setCode('The Code');

            $this->dm->persist($article);
            $this->dm->flush();
            $this->dm->clear();
            $this->assertEquals('my-title-the-code-'.($i + 1), $article->getSlug());
        }
    }

    public function testGithubIssue57()
    {
        // slug matched by prefix
        $article = new Article();
        $article->setTitle('my');
        $article->setCode('slug');
        $this->dm->persist($article);

        $article2 = new Article();
        $article2->setTitle('my');
        $article2->setCode('s');
        $this->dm->persist($article2);

        $this->dm->flush();
        $this->assertEquals('my-s', $article2->getSlug());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('My Title');
        $art0->setCode('The Code');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}

<?php

namespace Gedmo\Sluggable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\Sluggable\Document\Article;
use Gedmo\Sluggable\SluggableListener;

class SluggableDocumentTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Sluggable\Document\Article';

    private $dm;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldGenerateSlug()
    {
        $this->populate();
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

    /**
     * @test
     */
    function shouldGenerateUniqueSlug()
    {
        $this->populate();
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('My Title');
            $article->setCode('The Code');

            $this->dm->persist($article);
            $this->dm->flush();
            $this->dm->clear();
            $this->assertEquals('my-title-the-code-' . ($i + 1), $article->getSlug());
        }
    }

    /**
     * @test
     */
    function shouldFixGithubIssue57()
    {
        // slug matched by prefix
        $article = new Article;
        $article->setTitle('my');
        $article->setCode('slug');
        $this->dm->persist($article);

        $article2 = new Article;
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

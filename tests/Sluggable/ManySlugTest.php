<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\ArticleManySlug;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

class ManySlugTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Fixture\Sluggable\ArticleManySlug';

    private $articleId;

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
        ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldSupportMultipleSlugs()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertEquals('the-title-my-code', $article->getSlug());
        $this->assertEquals('the-unique-title', $article->getUniqueSlug());
    }

    /**
     * @test
     */
    public function shouldHandleOneOfUniqueSlugs()
    {
        $a0 = new ArticleManySlug();
        $a0->setTitle('the title');
        $a0->setCode('my code');
        $a0->setUniqueTitle('title');

        $this->em->persist($a0);

        $a1 = new ArticleManySlug();
        $a1->setTitle('the title');
        $a1->setCode('my code');
        $a1->setUniqueTitle('title');

        $this->em->persist($a1);
        $this->em->flush();

        $this->assertEquals('title', $a0->getUniqueSlug());
        $this->assertEquals('title-1', $a1->getUniqueSlug());
        // if its translated maybe should be different
        $this->assertEquals('the-title-my-code-1', $a0->getSlug());
        $this->assertEquals('the-title-my-code-2', $a1->getSlug());
    }

    private function populate()
    {
        $article = new ArticleManySlug();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');

        $this->em->persist($article);
        $this->em->flush();
        $this->articleId = $article->getId();
    }
}

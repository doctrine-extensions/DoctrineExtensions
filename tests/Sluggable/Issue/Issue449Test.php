<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Fixture\Sluggable\Issue449\Article;
use Gedmo\Sluggable\SluggableListener;

class Issue449Test extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Issue449\Article';
    const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    private $softDeleteableListener, $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->softDeleteableListener = new SoftDeleteableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->addFilter(
            self::SOFT_DELETEABLE_FILTER_NAME,
            'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter'
        );
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->createSchema($this->em, array(
            self::TARGET,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldBuildUniqueSlugAfterSoftDeleteFilterIsDisabled()
    {
        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();

        $slug = $article->getSlug();

        $this->em->remove($article);
        $this->em->flush();

        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->assertNotEquals($slug, $article->getSlug());
    }

    /**
     * @test
     */
    function shouldSuccessWhenManagedFilterHasAlreadyBeenDisabled()
    {
        // disable one managed doctrine filter
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();

        $slug = $article->getSlug();

        $this->em->remove($article);
        $this->em->flush();

        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->assertNotEquals($slug, $article->getSlug());
    }
}

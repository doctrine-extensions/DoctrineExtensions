<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\SoftDeletable\SoftDeletableListener;
use Sluggable\Fixture\Issue449\Article;

/**
 * These are tests for Sluggable behavior
 *
 * @author Craig Marvelley <craig.marvelley@gmail.com>
 * @link http://marvelley.com
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue449Test extends BaseTestCaseORM
{
    const TARGET = 'Sluggable\\Fixture\\Issue449\\Article';
    const SOFT_DELETABLE_FILTER_NAME = 'soft-deletable';

    private $softDeletableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $sluggableListener = new SluggableListener;
        $sluggableListener->addManagedFilter(self::SOFT_DELETABLE_FILTER_NAME, true);
        $evm->addEventSubscriber($sluggableListener);

        $this->softDeletableListener = new SoftDeletableListener();
        $evm->addEventSubscriber($this->softDeletableListener);

        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETABLE_FILTER_NAME, 'Gedmo\SoftDeletable\Filter\SoftDeletableFilter');

        $this->em = $this->getMockSqliteEntityManager($evm, $config);

        $this->em->getFilters()->enable(self::SOFT_DELETABLE_FILTER_NAME);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
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
}

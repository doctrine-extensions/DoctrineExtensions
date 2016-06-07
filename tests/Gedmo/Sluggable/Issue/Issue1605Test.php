<?php
/**
 * @license See the file LICENSE for copying permission
 */

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue1605\Article;
use Sluggable\Fixture\Issue1605\Page;
use Tool\BaseTestCaseORM;

class Issue1605Test extends BaseTestCaseORM
{
    const PARENT  = 'Sluggable\\Fixture\\Issue1605\\ParentEntity';
    const ARTICLE = 'Sluggable\\Fixture\\Issue1605\\Article';
    const PAGE    = 'Sluggable\\Fixture\\Issue1605\\Page';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::PARENT,
            self::ARTICLE,
            self::PAGE,
        );
    }

    /**
     * @test
     */
    public function shouldNotFailWithJoinedInheritanceAndMultipleEntitiesWithSameSlugPropertyOnPHP707()
    {
        $article = new Article();
        $article->setTitle('the title');
        $this->em->persist($article);

        $page = new Page();
        $page->setTitle('the title');
        $this->em->persist($page);

        $this->em->flush();
    }
}

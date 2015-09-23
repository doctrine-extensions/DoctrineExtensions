<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue1240\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue1240Test extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\Issue1240\\Article';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldWorkWithPlusAsSeparator()
    {
        $article = new Article();
        $article->setTitle('the title');
        $this->em->persist($article);

        $article2 = new Article();
        $article2->setTitle('the title');
        $this->em->persist($article2);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals('the+title', $article->getSlug());
        $this->assertEquals('The+Title', $article->getCamelSlug());

        $this->assertEquals('the+title+1', $article2->getSlug());
        $this->assertEquals('The+Title+1', $article2->getCamelSlug());

        $article = new Article();
        $article->setTitle('the title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals('the+title+2', $article->getSlug());
        $this->assertEquals('The+Title+2', $article->getCamelSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
        );
    }
}

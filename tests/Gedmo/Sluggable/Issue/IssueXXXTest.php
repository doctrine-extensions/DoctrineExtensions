<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Sluggable\SluggableListener;
use Sluggable\Fixture\IssueXXX\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IssueXXXTest extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\IssueXXX\\Article';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
//         $this->populate();
    }

    /**
     * @test
     */
    public function shouldTryPreferedSlugFirst()
    {
        $article = new Article();
        $article->setTitle('the title with number 1');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals('the-title-with-number-1', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        // the slug was 'the-title-with-number-2' before the fix here
        // despite the fact that there is no entity with slug 'the-title-with-number'
        $this->assertEquals('the-title-with-number', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals('the-title-with-number-2', $article->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
        );
    }
}

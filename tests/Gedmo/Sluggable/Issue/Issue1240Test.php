<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue1240\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue1240Test extends BaseTestCaseORM
{
    public const ARTICLE = 'Gedmo\\Tests\\Sluggable\\Fixture\\Issue1240\\Article';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

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

        static::assertEquals('the+title', $article->getSlug());
        static::assertEquals('The+Title', $article->getCamelSlug());

        static::assertEquals('the+title+1', $article2->getSlug());
        static::assertEquals('The+Title+1', $article2->getCamelSlug());

        $article = new Article();
        $article->setTitle('the title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertEquals('the+title+2', $article->getSlug());
        static::assertEquals('The+Title+2', $article->getCamelSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
    }
}

<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\CallbackSlug;
use Tool\BaseTestCaseORM;

class SluggableCallbackTest extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\CallbackSlug';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function testCallbackSlug()
    {
        $article = new CallbackSlug();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals('the-title-my-code', $article->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
        );
    }
}

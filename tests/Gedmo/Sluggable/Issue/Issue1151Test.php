<?php

namespace Gedmo\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Gedmo\Fixture\Sluggable\Issue1151\Article;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\TestTool\ObjectManagerTestCase;

/**
 * @author Vaidas Lažauskas <vaidas@notrix.lt>
 */
class Issue1151Test extends ObjectManagerTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * Test if new object with predefined id will be processed by sluggable listener
     */
    public function testSlugCreateOnNewArticle()
    {
        $article = new Article();
        $article->setId('ABC123');
        $article->setTitle('Test');
        $this->dm->persist($article);

        $this->dm->flush();
        $this->assertEquals('test', $article->getSlug());
    }
}

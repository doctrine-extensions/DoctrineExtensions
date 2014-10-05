<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue1151\Article;
use Tool\BaseTestCaseMongoODM;

/**
 * Gedmo\Sluggable\Issue1151Test
 *
 * @author Vaidas Lažauskas <vaidas@notrix.lt>
 */
class Issue1151Test extends BaseTestCaseMongoODM
{
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

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }
}

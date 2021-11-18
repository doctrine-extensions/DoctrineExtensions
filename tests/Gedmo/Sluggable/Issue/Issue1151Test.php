<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue1151\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * Gedmo\Sluggable\Issue1151Test
 *
 * @author Vaidas Lažauskas <vaidas@notrix.lt>
 */
final class Issue1151Test extends BaseTestCaseMongoODM
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
        static::assertSame('test', $article->getSlug());
    }

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }
}

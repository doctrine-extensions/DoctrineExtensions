<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

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
     * Set up test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }

    /**
     * Test if new object with predefined id will be processed by sluggable listener
     */
    public function testSlugCreateOnNewArticle(): void
    {
        $article = new Article();
        $article->setId('ABC123');
        $article->setTitle('Test');
        $this->dm->persist($article);

        $this->dm->flush();
        static::assertSame('test', $article->getSlug());
    }
}

<?php

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for Blameable behavior, when no user is available
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class NoUserTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        // create the document manager
        $this->getMockDocumentManager($evm);
    }

    public function testWhenNoUserIsAvailable()
    {
        $sport = new Article();
        $sport->setTitle('sport no user');

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport no user']);
        static::assertEmpty($sport->getCreated());
        static::assertEmpty($sport->getUpdated());
    }
}

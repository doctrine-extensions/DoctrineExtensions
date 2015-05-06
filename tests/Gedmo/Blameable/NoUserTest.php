<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Blameable\Fixture\Document\Article;

/**
 * These are tests for Blameable behavior, when no user is available
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NoUserTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Blameable\Fixture\Document\Article';

    protected function setUp()
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
        $sport = $repo->findOneByTitle('sport no user');
        $this->assertEmpty($sport->getCreated());
        $this->assertEmpty($sport->getUpdated());
    }
}

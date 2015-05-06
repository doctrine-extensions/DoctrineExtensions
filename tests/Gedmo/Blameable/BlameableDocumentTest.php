<?php

namespace Gedmo\Blameable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Blameable\Fixture\Document\Article;
use Blameable\Fixture\Document\Type;
use Blameable\Fixture\Document\User;

/**
 * These are tests for Blameable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableDocumentTest extends BaseTestCaseMongoODM
{
    const TEST_USERNAME = 'testuser';

    const TYPE = 'Blameable\Fixture\Document\Type';
    const USER = 'Blameable\Fixture\Document\User';
    const ARTICLE = 'Blameable\Fixture\Document\Article';

    protected function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->setUsername(self::TEST_USERNAME);

        $listener = new BlameableListener();
        $listener->setUserValue($user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $manager = $this->getMockDocumentManager($evm);
        $manager->persist($user);
        $this->populate();
        $manager->flush();
    }

    public function testBlameable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('Blameable Article');

        $this->assertEquals(self::TEST_USERNAME, $article->getCreated());
        $this->assertEquals(self::TEST_USERNAME, $article->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();

        $article = $repo->findOneByTitle('Blameable Article');

        $this->assertEquals(self::TEST_USERNAME, $article->getPublished());
        $this->assertEquals(self::TEST_USERNAME, $article->getCreator()->getUsername());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_USERNAME);
        $sport->setUpdated(self::TEST_USERNAME);

        $this->dm->persist($sport);
        $this->dm->flush();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(self::TEST_USERNAME, $sport->getCreated());
        $this->assertEquals(self::TEST_USERNAME, $sport->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_USERNAME);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(self::TEST_USERNAME, $sport->getPublished());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Blameable Article');

        $this->dm->persist($art0);
    }
}

<?php

namespace Gedmo\Blameable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Blameable\Fixture\Document\Article,
    Blameable\Fixture\Document\ArticleWithDifferentWhom,
    Blameable\Fixture\Document\Type,
    Blameable\Fixture\Document\User;

/**
 * These are tests for Blameable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @group blameable
 */
class BlameableDocumentTest extends BaseTestCaseMongoODM
{
    const TEST_USERNAME = 'testuser';

    const TYPE = 'Blameable\Fixture\Document\Type';
    const USER = 'Blameable\Fixture\Document\User';
    const ARTICLE = 'Blameable\Fixture\Document\Article';
    const ARTICLE_DIFFERENT_WHOM = 'Blameable\Fixture\Document\ArticleWithDifferentWhom';

    /** @var BlameableListener */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->setUsername(self::TEST_USERNAME);

        $this->listener = $listener = new BlameableListener();
        $listener->setUserValue($user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $manager = $this->getMockDocumentManager($evm);
        $manager->persist($user);
        $manager->flush();

        $this->populate();
    }

    public function testBlameable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('Blameable Article');

        $this->assertEquals(self::TEST_USERNAME, $article->getCreated());
        $this->assertEquals(self::TEST_USERNAME, $article->getUpdated());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

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
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(self::TEST_USERNAME, $sport->getCreated());
        $this->assertEquals(self::TEST_USERNAME, $sport->getUpdated());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_USERNAME);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(self::TEST_USERNAME, $sport->getPublished());
    }

    public function testDifferentWhom()
    {
        $this->listener->setUserValueFor('consumer', 'testconsumer');

        $whom = new ArticleWithDifferentWhom();
        $whom->setTitle('foobar');

        $this->dm->persist($whom);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE_DIFFERENT_WHOM);
        $whom = $repo->findOneByTitle('foobar');
        $this->assertEquals('testuser', $whom->getCreatedUser());
        $this->assertEquals('testuser', $whom->getUpdatedUser());
        $this->assertEquals('testconsumer', $whom->getCreatedConsumer());
        $this->assertEquals('testconsumer', $whom->getUpdatedConsumer());

        $this->listener->setUserValueFor('consumer', 'updatedconsumer');
        $this->listener->setUserValue('updateduser');

        $whom->setTitle('updated_foobar');
        $this->dm->flush();
        $this->dm->clear();
        $whom = $repo->findOneByTitle('updated_foobar');

        $this->assertEquals('testuser', $whom->getCreatedUser());
        $this->assertEquals('updateduser', $whom->getUpdatedUser());
        $this->assertEquals('testconsumer', $whom->getCreatedConsumer());
        $this->assertEquals('updatedconsumer', $whom->getUpdatedConsumer());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Blameable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
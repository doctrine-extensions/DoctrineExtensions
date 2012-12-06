<?php

namespace Gedmo\Blameable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Blameable\Fixture\Document\Article,
    Blameable\Fixture\Document\Type;

/**
 * These are tests for Blameable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Blameable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Blameable\Fixture\Document\Article';
    const TYPE = 'Blameable\Fixture\Document\Type';

    protected function setUp()
    {
        parent::setUp();

        $listener = new BlameableListener;
        $listener->setUserValue('testuser');

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testBlameable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('Blameable Article');

        $this->assertEquals('testuser', $article->getCreated());
        $this->assertEquals('testuser', $article->getUpdated());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneByTitle('Blameable Article');
        $this->assertEquals('testuser', $article->getPublished());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals('myuser', $sport->getCreated());
        $this->assertEquals('myuser', $sport->getUpdated());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished('myuser');
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals('myuser', $sport->getPublished());
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
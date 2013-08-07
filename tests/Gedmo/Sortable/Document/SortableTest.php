<?php

namespace Gedmo\Sortable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Sortable\SortableListener;
use Gedmo\Fixture\Sortable\Document\Article;
use Gedmo\Fixture\Sortable\Document\Type;

class SortableTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Sortable\Document\Article';
    const TYPE = 'Gedmo\Fixture\Sortable\Document\Type';

    private $dm;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);
        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldHandleSortableDocument()
    {
        $programmingLanguage = new Type;
        $programmingLanguage->setName('Programming Language');

        $library = new Type;
        $library->setName('Library');

        $php = new Article;
        $php->setTitle('php');
        $php->setType($programmingLanguage);

        $go = new Article;
        $go->setTitle('go');
        $go->setType($programmingLanguage);

        $jquery = new Article;
        $jquery->setTitle('jQuery');
        $jquery->setType($library);

        $this->dm->persist($programmingLanguage);
        $this->dm->persist($library);
        $this->dm->persist($php);
        $this->dm->persist($go);
        $this->dm->persist($jquery);

        $this->dm->flush();

        $this->assertSame(0, $php->getPosition());
        $this->assertSame(1, $go->getPosition());
        $this->assertSame(0, $jquery->getPosition());

        $cpp = new Article;
        $cpp->setTitle('C plus plus');
        $cpp->setType($programmingLanguage);

        $this->dm->persist($cpp);
        $this->dm->flush();

        $this->assertSame(2, $cpp->getPosition());

        $vanillajs = new Article;
        $vanillajs->setTitle('VanillaJs');
        $vanillajs->setType($library);

        $this->dm->persist($vanillajs);
        $this->dm->flush();

        $this->assertSame(1, $vanillajs->getPosition());

        // test realocation
        $cpp->setPosition(1);
        $this->dm->persist($cpp);
        $this->dm->flush();

        $this->assertSame(1, $cpp->getPosition());
        $this->assertSame(0, $php->getPosition());
        $this->assertSame(2, $go->getPosition());
    }

    /**
     * @test
     */
    function shouldHandleNullGroup()
    {
        $php = new Article;
        $php->setTitle('php');

        $go = new Article;
        $go->setTitle('go');

        $this->dm->persist($php);
        $this->dm->persist($go);

        $this->dm->flush();

        $this->assertSame(0, $php->getPosition());
        $this->assertSame(1, $go->getPosition());

        $cpp = new Article;
        $cpp->setTitle('C plus plus');
        $cpp->setPosition(45);

        $this->dm->persist($cpp);
        $this->dm->flush();

        $this->assertSame(45, $cpp->getPosition());
        $this->assertSame(0, $php->getPosition());
        $this->assertSame(1, $go->getPosition());

        $scala = new Article;
        $scala->setTitle('Scala');
        $scala->setPosition(-1);

        $this->dm->persist($scala);
        $this->dm->flush();

        $this->assertSame(46, $scala->getPosition());
        $this->assertSame(45, $cpp->getPosition());
        $this->assertSame(0, $php->getPosition());
        $this->assertSame(1, $go->getPosition());

        $haskel = new Article;
        $haskel->setTitle('Haskel');
        $haskel->setPosition(0);

        $this->dm->persist($haskel);
        $this->dm->flush();

        $this->assertSame(0, $haskel->getPosition());
        $this->assertSame(47, $scala->getPosition());
        $this->assertSame(46, $cpp->getPosition());
        $this->assertSame(1, $php->getPosition());
        $this->assertSame(2, $go->getPosition());
    }
}

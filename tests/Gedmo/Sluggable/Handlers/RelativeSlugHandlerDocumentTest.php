<?php

namespace Gedmo\Sluggable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Sluggable\Fixture\Document\Handler\Article;
use Sluggable\Fixture\Document\Handler\RelativeSlug;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RelativeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Sluggable\\Fixture\\Document\\Handler\\Article';
    const SLUG = 'Sluggable\\Fixture\\Document\\Handler\\RelativeSlug';

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $thomas = $repo->findOneByTitle('Thomas');
        $this->assertEquals('sport-test/thomas', $thomas->getSlug());

        $jen = $repo->findOneByTitle('Jen');
        $this->assertEquals('sport-test/jen', $jen->getSlug());

        $john = $repo->findOneByTitle('John');
        $this->assertEquals('cars-code/john', $john->getSlug());

        $single = $repo->findOneByTitle('Single');
        $this->assertEquals('single', $single->getSlug());
    }

    public function testUpdateOperations()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $thomas = $repo->findOneByTitle('Thomas');
        $thomas->setTitle('Ninja');
        $this->dm->persist($thomas);
        $this->dm->flush();

        $this->assertEquals('sport-test/ninja', $thomas->getSlug());

        $sport = $this->dm->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $sport->setTitle('Martial Arts');

        $this->dm->persist($sport);
        $this->dm->flush();

        $this->assertEquals('martial-arts-test', $sport->getSlug());

        $this->assertEquals('martial-arts-test/ninja', $thomas->getSlug());

        $jen = $repo->findOneByTitle('Jen');
        $this->assertEquals('martial-arts-test/jen', $jen->getSlug());

        $cars = $this->dm->getRepository(self::ARTICLE)->findOneByTitle('Cars');
        $jen->setArticle($cars);

        $this->dm->persist($jen);
        $this->dm->flush();

        $this->assertEquals('cars-code/jen', $jen->getSlug());
    }

    private function populate()
    {
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setCode('test');
        $this->dm->persist($sport);

        $cars = new Article();
        $cars->setTitle('Cars');
        $cars->setCode('code');
        $this->dm->persist($cars);

        $thomas = new RelativeSlug();
        $thomas->setTitle('Thomas');
        $thomas->setArticle($sport);
        $this->dm->persist($thomas);

        $jen = new RelativeSlug();
        $jen->setTitle('Jen');
        $jen->setArticle($sport);
        $this->dm->persist($jen);

        $john = new RelativeSlug();
        $john->setTitle('John');
        $john->setArticle($cars);
        $this->dm->persist($john);

        $single = new RelativeSlug();
        $single->setTitle('Single');
        $this->dm->persist($single);

        $this->dm->flush();
    }
}

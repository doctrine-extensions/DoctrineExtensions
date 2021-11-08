<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\Article;
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\RelativeSlug;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RelativeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const SLUG = RelativeSlug::class;

    protected function setUp(): void
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

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        static::assertEquals('sport-test/thomas', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertEquals('sport-test/jen', $jen->getSlug());

        $john = $repo->findOneBy(['title' => 'John']);
        static::assertEquals('cars-code/john', $john->getSlug());

        $single = $repo->findOneBy(['title' => 'Single']);
        static::assertEquals('single', $single->getSlug());
    }

    public function testUpdateOperations()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        $thomas->setTitle('Ninja');
        $this->dm->persist($thomas);
        $this->dm->flush();

        static::assertEquals('sport-test/ninja', $thomas->getSlug());

        $sport = $this->dm->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        $sport->setTitle('Martial Arts');

        $this->dm->persist($sport);
        $this->dm->flush();

        static::assertEquals('martial-arts-test', $sport->getSlug());

        static::assertEquals('martial-arts-test/ninja', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertEquals('martial-arts-test/jen', $jen->getSlug());

        $cars = $this->dm->getRepository(self::ARTICLE)->findOneBy(['title' => 'Cars']);
        $jen->setArticle($cars);

        $this->dm->persist($jen);
        $this->dm->flush();

        static::assertEquals('cars-code/jen', $jen->getSlug());
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

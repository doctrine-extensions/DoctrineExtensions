<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Article;
use Sluggable\Fixture\ArticleRelativeSlug;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RelativeSlugHandlerTest extends BaseTestCaseORM
{
    const SLUG = "Sluggable\\Fixture\\ArticleRelativeSlug";
    const ARTICLE = "Sluggable\\Fixture\\Article";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'tests',
            'user' => 'root',
            'password' => 'nimda'
        );
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::SLUG);

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
        $repo = $this->em->getRepository(self::SLUG);

        $thomas = $repo->findOneByTitle('Thomas');
        $thomas->setTitle('Ninja');
        $this->em->persist($thomas);
        $this->em->flush();

        $this->assertEquals('sport-test/ninja', $thomas->getSlug());

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $sport->setTitle('Martial Arts');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertEquals('martial-arts-test/ninja', $thomas->getSlug());

        $jen = $repo->findOneByTitle('Jen');
        $this->assertEquals('martial-arts-test/jen', $jen->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::SLUG,
            self::ARTICLE
        );
    }

    private function populate()
    {
        $sport = new Article;
        $sport->setTitle('Sport');
        $sport->setCode('test');
        $this->em->persist($sport);

        $cars = new Article;
        $cars->setTitle('Cars');
        $cars->setCode('code');
        $this->em->persist($cars);

        $thomas = new ArticleRelativeSlug;
        $thomas->setTitle('Thomas');
        $thomas->setArticle($sport);
        $this->em->persist($thomas);

        $jen = new ArticleRelativeSlug;
        $jen->setTitle('Jen');
        $jen->setArticle($sport);
        $this->em->persist($jen);

        $john = new ArticleRelativeSlug;
        $john->setTitle('John');
        $john->setArticle($cars);
        $this->em->persist($john);

        $single = new ArticleRelativeSlug;
        $single->setTitle('Single');
        $this->em->persist($single);

        $this->em->flush();
    }
}

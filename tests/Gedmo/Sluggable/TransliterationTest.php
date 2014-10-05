<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TransliterationTest extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\Article';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug()
    {
        $repo = $this->em->getRepository(self::ARTICLE);

        $lithuanian = $repo->findOneByCode('lt');
        $this->assertEquals('transliteration-test-usage-uz-lt', $lithuanian->getSlug());

        $bulgarian = $repo->findOneByCode('bg');
        $this->assertEquals('tova-ie-tiestovo-zaghlaviie-bg', $bulgarian->getSlug());

        $russian = $repo->findOneByCode('ru');
        $this->assertEquals('eto-tiestovyi-zagholovok-ru', $russian->getSlug());

        $german = $repo->findOneByCode('de');
        $this->assertEquals('fuhren-aktivitaten-haglofs-de', $german->getSlug());
    }

    private function populate()
    {
        $lithuanian = new Article();
        $lithuanian->setTitle('trąnslįteration tėst ųsąge ūž');
        $lithuanian->setCode('lt');

        $bulgarian = new Article();
        $bulgarian->setTitle('това е тестово заглавие');
        $bulgarian->setCode('bg');

        $russian = new Article();
        $russian->setTitle('это тестовый заголовок');
        $russian->setCode('ru');

        $german = new Article();
        $german->setTitle('führen Aktivitäten Haglöfs');
        $german->setCode('de');

        $this->em->persist($lithuanian);
        $this->em->persist($bulgarian);
        $this->em->persist($russian);
        $this->em->persist($german);
        $this->em->flush();
        $this->em->clear();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
        );
    }
}

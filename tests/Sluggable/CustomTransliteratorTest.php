<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Article;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CustomTransliteratorTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Fixture\Sluggable\Article';

    /**
     * @var EntityManager
     */
    private $em;

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testStandardTransliteratorFailsOnChineseCharacters()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
                self::ARTICLE,
            ));
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneByCode('zh');
        $this->assertEquals('zh', $chinese->getSlug());
    }

    public function testCanUseCustomTransliterator()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new MySluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
                self::ARTICLE,
            ));
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneByCode('zh');
        $this->assertEquals('bei-jing', $chinese->getSlug());
    }

    private function populate()
    {
        $chinese = new Article();
        $chinese->setTitle('北京');
        $chinese->setCode('zh');
        $this->em->persist($chinese);
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

class MySluggableListener extends SluggableListener
{
    public function __construct()
    {
        $this->setTransliterator(array('\Sluggable\Transliterator', 'transliterate'));
    }
}

class Transliterator
{
    public static function transliterate($text, $separator, $object)
    {
        return 'Bei Jing';
    }
}

<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\ConfigurationArticle;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableConfigurationTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Fixture\Sluggable\ConfigurationArticle';

    private $articleId;

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
                self::ARTICLE,
            ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testInsertedNewSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        $this->assertEquals('the-title-my-code', $article->getSlug());
    }

    public function testNonUniqueSlugGeneration()
    {
        for ($i = 0; $i < 5; $i++) {
            $article = new ConfigurationArticle();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            $this->assertEquals('the-title-my-code', $article->getSlug());
        }
    }

    public function testSlugLimit()
    {
        $long = 'the title the title the title the title the';
        $article = new ConfigurationArticle();
        $article->setTitle($long);
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $shorten = $article->getSlug();
        $this->assertEquals(32, strlen($shorten));
    }

    public function testNonUpdatableSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals('the-title-my-code', $article->getSlug());
    }

    private function populate()
    {
        $article = new ConfigurationArticle();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}

<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Sluggable\Fixture\Post,
    Sluggable\Fixture\Article;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableTest extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\Article';
    const POST    = 'Sluggable\\Fixture\\Post';
    
    private $articleId;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        $this->assertTrue($article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }

    public function testUniqueSlugGeneration()
    {
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            $this->assertEquals($article->getSlug(), 'the-title-my-code-' . ($i + 1));
        }
    }
    
    public function testPositionedSlugGeneration()
    {
         $post = new Post();
         $post->setTitle('the title');
         $post->setCode('my code');
         
         $this->em->persist($post);
         $this->em->flush();
         $this->em->clear();
         $this->assertEquals($post->getSlug(), 'my-code-the-title');
    }

    public function testUniqueSlugLimit()
    {
        $long = 'the title the title the title the title the title the title the title';
        $article = new Article();
        $article->setTitle($long);
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle($long);
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();

            $shorten = $article->getSlug();
            $this->assertEquals(strlen($shorten), 64);
            $expected = 'the-title-the-title-the-title-the-title-the-title-the-title-the-';
            $expected = substr($expected, 0, 64 - (strlen($i+1) + 1)) . '-' . ($i+1);
            $this->assertEquals($shorten, $expected);
        }
    }

    public function testUniqueNumberedSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code 123');

        $this->em->persist($article);
        $this->em->flush();
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code 123');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            $this->assertEquals($article->getSlug(), 'the-title-my-code-123-' . ($i + 1));
        }
    }

    public function testUpdatableSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($article->getSlug(), 'the-title-updated-my-code');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::POST
        );
    }

    private function populate()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
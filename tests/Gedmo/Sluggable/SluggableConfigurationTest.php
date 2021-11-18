<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\ConfigurationArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SluggableConfigurationTest extends BaseTestCaseORM
{
    public const ARTICLE = ConfigurationArticle::class;

    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        static::assertInstanceOf(Sluggable::class, $article);
        static::assertSame('the-title-my-code', $article->getSlug());
    }

    public function testNonUniqueSlugGeneration()
    {
        for ($i = 0; $i < 5; ++$i) {
            $article = new ConfigurationArticle();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            static::assertSame('the-title-my-code', $article->getSlug());
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
        static::assertSame(32, strlen($shorten));
    }

    public function testNonUpdatableSlug()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-my-code', $article->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
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

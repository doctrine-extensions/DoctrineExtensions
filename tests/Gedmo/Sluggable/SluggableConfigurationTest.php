<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
 */
final class SluggableConfigurationTest extends BaseTestCaseORM
{
    public const ARTICLE = ConfigurationArticle::class;

    /**
     * @var int|null
     */
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        static::assertInstanceOf(Sluggable::class, $article);
        static::assertSame('the-title-my-code', $article->getSlug());
    }

    public function testNonUniqueSlugGeneration(): void
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

    public function testSlugLimit(): void
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

    public function testNonUpdatableSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-my-code', $article->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }

    private function populate(): void
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

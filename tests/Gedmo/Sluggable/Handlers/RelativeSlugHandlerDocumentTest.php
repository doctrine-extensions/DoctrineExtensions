<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Handlers;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\Article;
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\RelativeSlug;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RelativeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const SLUG = RelativeSlug::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultDocumentManager($evm);
    }

    public function testSlugGeneration(): void
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        static::assertSame('sport-test/thomas', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertSame('sport-test/jen', $jen->getSlug());

        $john = $repo->findOneBy(['title' => 'John']);
        static::assertSame('cars-code/john', $john->getSlug());

        $single = $repo->findOneBy(['title' => 'Single']);
        static::assertSame('single', $single->getSlug());
    }

    public function testUpdateOperations(): void
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $thomas = $repo->findOneBy(['title' => 'Thomas']);
        $thomas->setTitle('Ninja');
        $this->dm->persist($thomas);
        $this->dm->flush();

        static::assertSame('sport-test/ninja', $thomas->getSlug());

        $sport = $this->dm->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        $sport->setTitle('Martial Arts');

        $this->dm->persist($sport);
        $this->dm->flush();

        static::assertSame('martial-arts-test', $sport->getSlug());

        static::assertSame('martial-arts-test/ninja', $thomas->getSlug());

        $jen = $repo->findOneBy(['title' => 'Jen']);
        static::assertSame('martial-arts-test/jen', $jen->getSlug());

        $cars = $this->dm->getRepository(self::ARTICLE)->findOneBy(['title' => 'Cars']);
        $jen->setArticle($cars);

        $this->dm->persist($jen);
        $this->dm->flush();

        static::assertSame('cars-code/jen', $jen->getSlug());
    }

    private function populate(): void
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

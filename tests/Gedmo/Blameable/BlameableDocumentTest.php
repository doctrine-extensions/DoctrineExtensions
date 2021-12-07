<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Document\Article;
use Gedmo\Tests\Blameable\Fixture\Document\Type;
use Gedmo\Tests\Blameable\Fixture\Document\User;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for Blameable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class BlameableDocumentTest extends BaseTestCaseMongoODM
{
    public const TEST_USERNAME = 'testuser';

    public const TYPE = Type::class;
    public const USER = User::class;
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User();
        $user->setUsername(self::TEST_USERNAME);

        $listener = new BlameableListener();
        $listener->setUserValue($user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $manager = $this->getDefaultDocumentManager($evm);
        $manager->persist($user);
        $this->populate();
        $manager->flush();
    }

    public function testBlameable(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Blameable Article']);

        static::assertSame(self::TEST_USERNAME, $article->getCreated());
        static::assertSame(self::TEST_USERNAME, $article->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();

        $article = $repo->findOneBy(['title' => 'Blameable Article']);

        static::assertSame(self::TEST_USERNAME, $article->getPublished());
        static::assertSame(self::TEST_USERNAME, $article->getCreator()->getUsername());
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_USERNAME);
        $sport->setUpdated(self::TEST_USERNAME);

        $this->dm->persist($sport);
        $this->dm->flush();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_USERNAME, $sport->getCreated());
        static::assertSame(self::TEST_USERNAME, $sport->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_USERNAME);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_USERNAME, $sport->getPublished());
    }

    private function populate(): void
    {
        $art0 = new Article();
        $art0->setTitle('Blameable Article');

        $this->dm->persist($art0);
    }
}

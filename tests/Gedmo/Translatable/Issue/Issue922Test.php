<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue922\Post;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

final class Issue922Test extends BaseTestCaseORM
{
    public const POST = Post::class;
    public const TRANSLATION = Translation::class;

    public const TREE_WALKER_TRANSLATION = TranslationWalker::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTranslateDateFields(): void
    {
        $p1 = new Post();
        $p1->setPublishedAt(new \DateTime());
        $p1->setTimestampAt(new \DateTime());
        $p1->setDateAt(new \DateTime());
        $p1->setBoolean(true);

        $this->em->persist($p1);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $p1->setBoolean(false);

        $this->em->persist($p1);
        $this->em->flush();

        // clear and test postLoad event values set
        $this->em->clear();

        $p1 = $this->em->find(self::POST, $p1->getId());
        static::assertInstanceOf('DateTime', $p1->getPublishedAt());
        static::assertInstanceOf('DateTime', $p1->getTimestampAt());
        static::assertInstanceOf('DateTime', $p1->getDateAt());
        static::assertFalse($p1->getBoolean());

        // clear and test query hint hydration
        $this->em->clear();
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $q = $this->em->createQuery('SELECT p FROM '.self::POST.' p');
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'de');

        $p1 = $q->getSingleResult();
        static::assertInstanceOf('DateTime', $p1->getPublishedAt());
        static::assertInstanceOf('DateTime', $p1->getTimestampAt());
        static::assertInstanceOf('DateTime', $p1->getDateAt());
        static::assertFalse($p1->getBoolean());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::POST,
            self::TRANSLATION,
        ];
    }
}

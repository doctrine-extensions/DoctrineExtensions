<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Article;
use Gedmo\Tests\Translatable\Fixture\Comment;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableEntityDefaultTranslationTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * @var TranslationRepository
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('translatedLocale');
        $this->translatableListener->setDefaultLocale('defaultLocale');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);

        $this->repo = $this->em->getRepository(self::TRANSLATION);
    }

    // --- Tests for default translation overruling the translated entity
    //     property ------------------------------------------------------------

    public function testTranslatedPropertyWithoutPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithoutPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    // --- Tests for default translation making it into the entity's
    //     database row --------------------------------------------------------

    public function testOnlyDefaultTranslationWithoutPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(0, $trans);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testOnlyDefaultTranslationWithPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title defaultLocale', $trans['defaultLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testUpdateTranslationInDefaultLocale(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->find(self::ARTICLE, 1);
        $entity->setTranslatableLocale('translatedLocale');
        $this->em->refresh($entity);

        $this->repo
             ->translate($entity, 'title', 'defaultLocale', 'update title defaultLocale');

        $this->em->flush();

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
           ->from(self::ARTICLE, 'a')
           ->where('a.id = 1');

        $fields = $qb->getQuery()->getArrayResult();

        static::assertSame('update title defaultLocale', $fields[0]['title']);
    }

    public function testUpdateTranslationWithPersistingInDefaultLocale(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->find(self::ARTICLE, 1);
        $entity->setTranslatableLocale('translatedLocale');
        $this->em->refresh($entity);

        $this->repo
             ->translate($entity, 'title', 'defaultLocale', 'update title defaultLocale');

        $this->em->flush();

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
           ->from(self::ARTICLE, 'a')
           ->where('a.id = 1');

        $fields = $qb->getQuery()->getArrayResult();

        static::assertSame('update title defaultLocale', $fields[0]['title']);
    }

    /**
     * As this test does not provide a default translation, we assert
     * that a translated value is picked as default value
     */
    public function testOnlyEntityTranslationWithoutPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title translatedLocale', $articles[0]['title']);
    }

    /**
     * As this test does not provide a default translation, we assert
     * that a translated value is picked as default value
     */
    public function testOnlyEntityTranslationWithPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title translatedLocale', $articles[0]['title']);
    }

    public function testDefaultAndEntityTranslationWithoutPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testDefaultAndEntityTranslationWithoutPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testDefaultAndEntityTranslationWithPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(2, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('title defaultLocale', $trans['defaultLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testDefaultAndEntityTranslationWithPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(2, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('title defaultLocale', $trans['defaultLocale']['title']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
    }

    public function testTwoFieldsWithoutPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'content', 'translatedLocale', 'content translatedLocale')
            ->translate($entity, 'content', 'defaultLocale', 'content defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('content translatedLocale', $trans['translatedLocale']['content']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
        static::assertSame('content defaultLocale', $articles[0]['content']);
    }

    public function testTwoFieldsWithoutPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'content', 'defaultLocale', 'content defaultLocale')
            ->translate($entity, 'content', 'translatedLocale', 'content translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(1, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('content translatedLocale', $trans['translatedLocale']['content']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
        static::assertSame('content defaultLocale', $articles[0]['content']);
    }

    public function testTwoFieldsWithPersistingDefault(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'content', 'translatedLocale', 'content translatedLocale')
            ->translate($entity, 'content', 'defaultLocale', 'content defaultLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(2, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('title defaultLocale', $trans['defaultLocale']['title']);
        static::assertSame('content translatedLocale', $trans['translatedLocale']['content']);
        static::assertSame('content defaultLocale', $trans['defaultLocale']['content']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
        static::assertSame('content defaultLocale', $articles[0]['content']);
    }

    public function testTwoFieldsWithPersistingDefaultResorted(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'content', 'defaultLocale', 'content defaultLocale')
            ->translate($entity, 'content', 'translatedLocale', 'content translatedLocale')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $trans = $this->repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(2, $trans);
        static::assertSame('title translatedLocale', $trans['translatedLocale']['title']);
        static::assertSame('title defaultLocale', $trans['defaultLocale']['title']);
        static::assertSame('content translatedLocale', $trans['translatedLocale']['content']);
        static::assertSame('content defaultLocale', $trans['defaultLocale']['content']);

        $articles = $this->em->createQuery('SELECT a FROM '.self::ARTICLE.' a')->getArrayResult();
        static::assertCount(1, $articles);
        static::assertSame('title defaultLocale', $articles[0]['title']);
        static::assertSame('content defaultLocale', $articles[0]['content']);
    }

    // --- Fixture related methods ---------------------------------------------

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}

<?php

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
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
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

        $conn = [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda',
        ];
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getDefaultMockSqliteEntityManager($evm);

        $this->repo = $this->em->getRepository(self::TRANSLATION);
    }

    // --- Tests for default translation overruling the translated entity
    //     property ------------------------------------------------------------

    public function testTranslatedPropertyWithoutPersistingDefault()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithoutPersistingDefaultResorted()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithPersistingDefault()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $entity = new Article();
        $this->repo
            ->translate($entity, 'title', 'defaultLocale', 'title defaultLocale')
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        static::assertSame('title translatedLocale', $entity->getTitle());
    }

    public function testTranslatedPropertyWithPersistingDefaultResorted()
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

    public function testOnlyDefaultTranslationWithoutPersistingDefault()
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

    public function testOnlyDefaultTranslationWithPersistingDefault()
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

    public function testUpdateTranslationInDefaultLocale()
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

        $qb = $this->em->createQueryBuilder('a');
        $qb->select('a')
           ->from(self::ARTICLE, 'a')
           ->where('a.id = 1');

        $fields = $qb->getQuery()->getArrayResult();

        static::assertSame('update title defaultLocale', $fields[0]['title']);
    }

    public function testUpdateTranslationWithPersistingInDefaultLocale()
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

        $qb = $this->em->createQueryBuilder('a');
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
    public function testOnlyEntityTranslationWithoutPersistingDefault()
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
    public function testOnlyEntityTranslationWithPersistingDefault()
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

    public function testDefaultAndEntityTranslationWithoutPersistingDefault()
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

    public function testDefaultAndEntityTranslationWithoutPersistingDefaultResorted()
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

    public function testDefaultAndEntityTranslationWithPersistingDefault()
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

    public function testDefaultAndEntityTranslationWithPersistingDefaultResorted()
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

    public function testTwoFieldsWithoutPersistingDefault()
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

    public function testTwoFieldsWithoutPersistingDefaultResorted()
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

    public function testTwoFieldsWithPersistingDefault()
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

    public function testTwoFieldsWithPersistingDefaultResorted()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}

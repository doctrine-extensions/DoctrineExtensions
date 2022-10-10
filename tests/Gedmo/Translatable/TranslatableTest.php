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
use Gedmo\Tests\Translatable\Fixture\Sport;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Translatable;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const SPORT = Sport::class;
    public const COMMENT = Comment::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var int|null
     */
    private $articleId;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldUpdateTranslationInDefaultLocaleIssue751(): void
    {
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $repo = $this->em->getRepository(self::ARTICLE);

        $entity = new Article();
        $entity->setTranslatableLocale('de');
        $entity->setTitle('test');
        $this->em->persist($entity);
        $this->em->flush();

        $entity->setTranslatableLocale('de');
        $entity->setTitle('test!');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        // this will force it to find translation in "en" locale, since listener has "en" set
        // and since default locale is "en" current translation will be "test"
        // setting title to "test" will not even persist entity, since there is no changeset
        $entity = $repo->findOneBy(['id' => $entity->getId()]);
        $entity->setTranslatableLocale('de');
        $entity->setTitle('test');
        $this->em->persist($entity);
        $this->em->flush();

        $this->em->clear();
        $entity = $repo->findOneBy(['id' => $entity->getId()]);
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($entity);
        static::assertArrayHasKey('de', $translations);
        static::assertSame('test!', $translations['de']['title']); // de translation was not updated, no changeset
        static::assertSame('test', $entity->getTitle()); // obviously "test" a default en translation
    }

    public function testShouldPersistDefaultLocaleTranslationIfRequired(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);

        $article = new Article();
        $article->setTitle('title in en');
        $article->setContent('content in en');

        $this->em->persist($article);
        $this->em->flush();

        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('en_us', $translations);
    }

    public function testShouldGenerateTranslations(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $repo);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        static::assertInstanceOf(Translatable::class, $article);

        $translations = $repo->findTranslations($article);
        static::assertCount(0, $translations);

        $comments = $article->getComments();
        static::assertCount(2, $comments);
        foreach ($comments as $num => $comment) {
            $translations = $repo->findTranslations($comment);

            static::assertCount(0, $translations);
        }
        // test default locale
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $qb = $this->em->createQueryBuilder();
        $qb->select('art')
            ->from(self::ARTICLE, 'art')
            ->where('art.id = :id');
        $q = $qb->getQuery();
        $result = $q->execute(
            ['id' => $article->getId()],
            \Doctrine\ORM\Query::HYDRATE_ARRAY
        );
        static::assertCount(1, $result);
        static::assertSame('title in en', $result[0]['title']);
        static::assertSame('content in en', $result[0]['content']);

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);

        static::assertArrayHasKey('content', $translations['de_de']);
        static::assertSame('content in de', $translations['de_de']['content']);

        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertSame('title in de', $translations['de_de']['title']);

        // test second translations
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            $comment->setTranslatableLocale('de_de');
            $comment->setSubject("subject{$number} in de");
            $comment->setMessage("message{$number} in de");
            $this->em->persist($comment);
        }
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);

        static::assertArrayHasKey('content', $translations['de_de']);
        static::assertSame('content in de', $translations['de_de']['content']);

        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertSame('title in de', $translations['de_de']['title']);

        $comments = $article->getComments();
        static::assertCount(2, $comments);
        foreach ($comments as $comment) {
            $translations = $repo->findTranslations($comment);

            static::assertCount(1, $translations);
            static::assertArrayHasKey('de_de', $translations);

            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            static::assertArrayHasKey('subject', $translations['de_de']);
            $expected = "subject{$number} in de";
            static::assertSame($expected, $translations['de_de']['subject']);

            static::assertArrayHasKey('message', $translations['de_de']);
            $expected = "message{$number} in de";
            static::assertSame($expected, $translations['de_de']['message']);
        }

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        static::assertSame('title in en', $article->getTitle());
        static::assertSame('content in en', $article->getContent());

        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());

            static::assertSame("subject{$number} in en", $comment->getSubject());
            static::assertSame("message{$number} in en", $comment->getMessage());
        }
        // test deletion
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->em->remove($article);
        $this->em->flush();

        $translations = $repo->findTranslations($article);
        static::assertCount(0, $translations);
    }

    public function testShouldSolveTranslationFallbackGithubIssue9(): void
    {
        $this->populate();
        $this->translatableListener->setTranslationFallback(false);
        $this->translatableListener->setTranslatableLocale('ru_RU');

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        static::assertFalse((bool) $article->getTitle());
        static::assertFalse((bool) $article->getContent());

        foreach ($article->getComments() as $comment) {
            static::assertFalse((bool) $comment->getSubject());
            static::assertFalse((bool) $comment->getMessage());
        }
        $this->em->clear();
        $this->translatableListener->setTranslationFallback(true);
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        static::assertSame('title in en', $article->getTitle());
        static::assertSame('content in en', $article->getContent());
    }

    public function testShouldSolveGithubIssue64(): void
    {
        $judo = new Sport();
        $judo->setTitle('Judo');
        $judo->setDescription('Whatever');

        $this->em->persist($judo);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de_de');

        $judo->setTitle('Judo');
        $judo->setDescription('Something in changeset');
        $this->em->persist($judo);
        $this->em->flush();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($judo);
        static::assertCount(1, $translations);

        // now without any changeset
        $this->translatableListener->setTranslatableLocale('ru_ru');

        $judo->setTitle('Judo');
        $this->em->persist($judo);
        $this->em->flush();

        // this will not add additional translation, because it cannot be tracked
        // without anything in changeset
        $translations = $repo->findTranslations($judo);
        static::assertCount(1, $translations);
    }

    public function testShouldRespectFallbackOption(): void
    {
        $article = new Article();
        $article->setTitle('Euro2012');
        $article->setAuthor('Shevchenko');
        $article->setViews(10);

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('ua_UA');
        $this->translatableListener->setTranslationFallback(true);
        $article = $this->em->find(self::ARTICLE, $article->getId());

        static::assertSame('Euro2012', $article->getTitle());
        static::assertSame('Shevchenko', $article->getAuthor());
        static::assertEmpty($article->getViews());

        $this->em->clear();
        $this->translatableListener->setTranslationFallback(false);
        $article = $this->em->find(self::ARTICLE, $article->getId());
        static::assertEmpty($article->getTitle());
        static::assertSame('Shevchenko', $article->getAuthor());
        static::assertEmpty($article->getViews());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
            self::SPORT,
        ];
    }

    private function populate(): void
    {
        $article = new Article();
        $article->setTitle('title in en');
        $article->setContent('content in en');

        $comment1 = new Comment();
        $comment1->setSubject('subject1 in en');
        $comment1->setMessage('message1 in en');

        $comment2 = new Comment();
        $comment2->setSubject('subject2 in en');
        $comment2->setMessage('message2 in en');

        $article->addComment($comment1);
        $article->addComment($comment2);

        $this->em->persist($article);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->articleId = $article->getId();
        $this->em->clear();
    }
}

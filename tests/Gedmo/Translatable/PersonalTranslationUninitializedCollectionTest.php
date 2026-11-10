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
use Gedmo\Tests\Translatable\Fixture\Personal\UninitializedCollection\Article;
use Gedmo\Tests\Translatable\Fixture\Personal\UninitializedCollection\ArticleTranslation;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableOrmAdapter;
use Gedmo\Translatable\TranslatableListener;

final class PersonalTranslationUninitializedCollectionTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en');
        $translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($translatableListener);
        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testLoadTranslationsReadsAnInitializedPersonalTranslationCollection(): void
    {
        $article = $this->loadArticle();
        $translationsProperty = new \ReflectionProperty(Article::class, 'translations');
        static::assertTrue($translationsProperty->isInitialized($article));

        $result = $this->loadTranslations($article, 'lt');

        static::assertSame([
            [
                'field' => 'title',
                'content' => 'lt',
            ],
        ], $result);
    }

    public function testLoadTranslationsQueriesWhenPersonalTranslationCollectionIsUninitialized(): void
    {
        $article = $this->loadArticle();
        $metadata = $this->em->getClassMetadata(Article::class);
        // find() injects a PersistentCollection. Restore the uninitialized typed
        // property, which ClassMetadata::getFieldValue() then reports as null.
        $metadata->setFieldValue($article, 'translations', null);

        $translationsProperty = new \ReflectionProperty(Article::class, 'translations');
        static::assertFalse($translationsProperty->isInitialized($article));
        static::assertNull($metadata->getFieldValue($article, 'translations'));

        $result = $this->loadTranslations($article, 'lt');

        static::assertSame([
            [
                'content' => 'lt',
                'field' => 'title',
            ],
        ], $result);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Article::class,
            ArticleTranslation::class,
        ];
    }

    private function loadArticle(): Article
    {
        $article = new Article();
        $article->setTitle('en');
        $this->em->persist($article);
        $this->em->flush();

        $ltTranslation = new ArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $deTranslation = new ArticleTranslation();
        $deTranslation
            ->setField('title')
            ->setContent('de')
            ->setObject($article)
            ->setLocale('de')
        ;
        $this->em->persist($ltTranslation);
        $this->em->persist($deTranslation);
        $this->em->flush();
        $id = $article->getId();
        $this->em->clear();

        $loaded = $this->em->find(Article::class, $id);
        static::assertInstanceOf(Article::class, $loaded);

        return $loaded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTranslations(Article $article, string $locale): array
    {
        $warnings = [];
        $previous = null;
        $previous = set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$warnings, &$previous): bool {
            if (E_WARNING === $severity) {
                $warnings[] = $message;

                return true;
            }

            if (is_callable($previous)) {
                return (bool) $previous($severity, $message, $file, $line);
            }

            return false;
        });

        try {
            $adapter = new TranslatableOrmAdapter();
            $adapter->setEntityManager($this->em);
            /** @var array<int, array<string, mixed>> $result */
            $result = $adapter->loadTranslations($article, ArticleTranslation::class, $locale, Article::class);
        } finally {
            restore_error_handler();
        }

        static::assertSame([], $warnings);

        return $result;
    }
}

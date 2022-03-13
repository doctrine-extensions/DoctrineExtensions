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
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Translatable\Fixture\Issue165\SimpleArticle;
use Gedmo\Translatable\Document\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue165Test extends BaseTestCaseMongoODM
{
    public const ARTICLE = SimpleArticle::class;
    public const TRANSLATION = Translation::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultDocumentManager($evm);
    }

    public function testShouldPersistUntranslatedFields(): void
    {
        $article = new SimpleArticle();
        $article->setTitle('en');
        $article->setContent('en');
        $article->setUntranslated('en');

        $this->dm->persist($article);
        $this->dm->flush();

        static::assertSame('en', $article->getUntranslated());

        $this->translatableListener->setTranslatableLocale('ru');

        $article->setTitle('ru');
        $article->setContent('ru');
        $article->setUntranslated('ru');

        $this->dm->persist($article);
        $this->dm->flush();

        static::assertSame('ru', $article->getUntranslated());

        $this->translatableListener->setTranslatableLocale('de');

        $newarticle = new SimpleArticle();
        $newarticle->setTitle('de');
        $newarticle->setContent('de');
        $newarticle->setUntranslated('de');

        $this->dm->persist($newarticle);
        $this->dm->flush();
        $this->dm->refresh($article);

        static::assertSame('de', $newarticle->getUntranslated());

        $this->translatableListener->setTranslatableLocale('en');

        $id = $newarticle->getId();
        $newarticle = $this->dm->getRepository(SimpleArticle::class)->find($id);

        $newarticle->setTitle('en');
        $newarticle->setContent('en');
        $newarticle->setUntranslated('en');

        $this->dm->persist($newarticle);
        $this->dm->flush();
        $this->dm->refresh($newarticle);

        static::assertSame('en', $newarticle->getUntranslated());

        $this->translatableListener->setTranslatableLocale('de');
        $newarticle->setTitle('de2');
        $newarticle->setContent('de2');
        $newarticle->setUntranslated('de2');

        $this->dm->persist($newarticle);
        $this->dm->flush();

        $id = $newarticle->getId();
        $newarticle = $this->dm->getRepository(SimpleArticle::class)->find($id);

        static::assertSame('de2', $newarticle->getUntranslated());
    }
}

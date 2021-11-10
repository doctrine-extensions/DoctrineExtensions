<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Article;
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
final class Issue84Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testIssue84()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);

        $article = new Article();
        $article->setTitle('en art');
        $article->setContent('content');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $article = $this->em->getReference(self::ARTICLE, 1);
        static::assertInstanceOf(Proxy::class, $article);

        $trans = $repo->findTranslations($article);
        static::assertCount(1, $trans);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}

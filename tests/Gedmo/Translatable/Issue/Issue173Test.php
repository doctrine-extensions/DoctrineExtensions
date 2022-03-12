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
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue173\Article;
use Gedmo\Tests\Translatable\Fixture\Issue173\Category;
use Gedmo\Tests\Translatable\Fixture\Issue173\Product;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Oscar Balladares liebegrube@gmail.com https://github.com/oscarballadares
 */
final class Issue173Test extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const ARTICLE = Article::class;
    public const PRODUCT = Product::class;
    public const TRANSLATION = Translation::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);

        $this->populate();
    }

    public function testIssue173(): void
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $categories = $this->getCategoriesThatHasNoAssociations();
        static::assertCount(1, $categories, '$category3 has no associations');
    }

    public function getCategoriesThatHasNoAssociations()
    {
        $query = $this->em->createQueryBuilder();
        $query2 = $this->em->createQueryBuilder();
        $query3 = $this->em->createQueryBuilder();
        $dql1 = $query2
            ->select('c1')
            ->from(self::CATEGORY, 'c1')
            ->join('c1.products', 'p')
            ->getDQL()
        ;
        $dql2 = $query3
            ->select('c2')
            ->from(self::CATEGORY, 'c2')
            ->join('c2.articles', 'a')
            ->getDQL()
        ;
        $query
            ->select('c')
            ->from(self::CATEGORY, 'c')
            ->where($query->expr()->notIn('c.id', $dql1))
            ->andWhere($query->expr()->notIn('c.id', $dql2))
            ;

        return $query->getQuery()->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            TranslationWalker::class
        )->getResult();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::ARTICLE,
            self::PRODUCT,
            self::TRANSLATION,
        ];
    }

    private function populate(): void
    {
        //Categories
        $category1 = new Category();
        $category1->setTitle('en category1');

        $category2 = new Category();
        $category2->setTitle('en category2');

        $category3 = new Category();
        $category3->setTitle('en category3');

        $this->em->persist($category1);
        $this->em->persist($category2);
        $this->em->persist($category3);
        $this->em->flush();

        //Articles
        $article1 = new Article();
        $article1->setTitle('en article1');
        $article1->setCategory($category1);

        //Products
        $product1 = new Product();
        $product1->setTitle('en product1');
        $product1->setCategory($category2);

        $this->em->persist($article1);
        $this->em->persist($product1);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('es');

        // Categories
        $category1->setTitle('es title');
        $category2->setTitle('es title');
        $category3->setTitle('es title');

        $article1->setTitle('es title');
        $product1->setTitle('es name');

        $this->em->persist($category1);
        $this->em->persist($category2);
        $this->em->persist($category3);
        $this->em->persist($article1);
        $this->em->persist($product1);

        $this->em->flush();
    }
}

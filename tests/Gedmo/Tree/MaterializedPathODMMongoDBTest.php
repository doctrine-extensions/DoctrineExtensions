<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Gedmo\Exception\RuntimeException;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Tree\Fixture\Document\Category;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathODMMongoDBTest extends BaseTestCaseMongoODM
{
    private const CATEGORY = Category::class;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var TreeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultDocumentManager($evm);

        $meta = $this->dm->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->dm, $meta->getName());
    }

    public function testInsertUpdateAndRemove(): void
    {
        // Insert
        $category = $this->createCategory();
        $category->setTitle('1');
        $category2 = $this->createCategory();
        $category2->setTitle('2');
        $category3 = $this->createCategory();
        $category3->setTitle('3');
        $category4 = $this->createCategory();
        $category4->setTitle('4');

        $category2->setParent($category);
        $category3->setParent($category2);

        $this->dm->persist($category4);
        $this->dm->persist($category3);
        $this->dm->persist($category2);
        $this->dm->persist($category);
        $this->dm->flush();

        $this->dm->refresh($category);
        $this->dm->refresh($category2);
        $this->dm->refresh($category3);
        $this->dm->refresh($category4);

        static::assertSame($this->generatePath(['1' => $category->getId()]), $category->getPath());
        static::assertSame($this->generatePath(['1' => $category->getId(), '2' => $category2->getId()]), $category2->getPath());
        static::assertSame($this->generatePath(['1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        static::assertSame($this->generatePath(['4' => $category4->getId()]), $category4->getPath());
        static::assertSame(1, $category->getLevel());
        static::assertSame(2, $category2->getLevel());
        static::assertSame(3, $category3->getLevel());
        static::assertSame(1, $category4->getLevel());

        // Update
        $category2->setParent(null);

        $this->dm->persist($category2);
        $this->dm->flush();

        $this->dm->refresh($category);
        $this->dm->refresh($category2);
        $this->dm->refresh($category3);

        static::assertSame($this->generatePath(['1' => $category->getId()]), $category->getPath());
        static::assertSame($this->generatePath(['2' => $category2->getId()]), $category2->getPath());
        static::assertSame($this->generatePath(['2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        static::assertSame(1, $category->getLevel());
        static::assertSame(1, $category2->getLevel());
        static::assertSame(2, $category3->getLevel());
        static::assertSame(1, $category4->getLevel());

        // Remove
        $this->dm->remove($category);
        $this->dm->remove($category2);
        $this->dm->flush();

        $result = $this->dm->createQueryBuilder()->find(self::CATEGORY)->getQuery()->execute();

        static::assertInstanceOf(Iterator::class, $result);

        /** @var Category $firstResult */
        $firstResult = $result->current();

        static::assertCount(1, $result->toArray());
        static::assertSame('4', $firstResult->getTitle());
        static::assertSame(1, $firstResult->getLevel());
    }

    public function testUseOfSeparatorInPathSourceShouldThrowAnException(): void
    {
        $this->expectException(RuntimeException::class);

        $category = $this->createCategory();
        $category->setTitle('1'.$this->config['path_separator']);

        $this->dm->persist($category);
        $this->dm->flush();
    }

    public function createCategory(): Category
    {
        $class = self::CATEGORY;

        return new $class();
    }

    public function generatePath(array $sources): string
    {
        $path = '';

        foreach ($sources as $p => $id) {
            $path .= $p.'-'.$id.$this->config['path_separator'];
        }

        return $path;
    }
}

<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Exception\RuntimeException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\MPCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class MaterializedPathORMTest extends BaseTestCaseORM
{
    public const CATEGORY = MPCategory::class;

    protected $config;
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
    }

    /**
     * @test
     */
    public function insertUpdateAndRemove()
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

        $this->em->persist($category4);
        $this->em->persist($category3);
        $this->em->persist($category2);
        $this->em->persist($category);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);
        $this->em->refresh($category4);

        static::assertEquals($this->generatePath(['1' => $category->getId()]), $category->getPath());
        static::assertEquals($this->generatePath(['1' => $category->getId(), '2' => $category2->getId()]), $category2->getPath());
        static::assertEquals($this->generatePath(['1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        static::assertEquals($this->generatePath(['4' => $category4->getId()]), $category4->getPath());
        static::assertEquals(1, $category->getLevel());
        static::assertEquals(2, $category2->getLevel());
        static::assertEquals(3, $category3->getLevel());
        static::assertEquals(1, $category4->getLevel());

        static::assertEquals('1-4', $category->getTreeRootValue());
        static::assertEquals('1-4', $category2->getTreeRootValue());
        static::assertEquals('1-4', $category3->getTreeRootValue());
        static::assertEquals('4-1', $category4->getTreeRootValue());

        // Update
        $category2->setParent(null);

        $this->em->persist($category2);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);

        static::assertEquals($this->generatePath(['1' => $category->getId()]), $category->getPath());
        static::assertEquals($this->generatePath(['2' => $category2->getId()]), $category2->getPath());
        static::assertEquals($this->generatePath(['2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        static::assertEquals(1, $category->getLevel());
        static::assertEquals(1, $category2->getLevel());
        static::assertEquals(2, $category3->getLevel());
        static::assertEquals(1, $category4->getLevel());

        static::assertEquals('1-4', $category->getTreeRootValue());
        static::assertEquals('2-3', $category2->getTreeRootValue());
        static::assertEquals('2-3', $category3->getTreeRootValue());
        static::assertEquals('4-1', $category4->getTreeRootValue());

        // Remove
        $this->em->remove($category);
        $this->em->remove($category2);
        $this->em->flush();

        $result = $this->em->createQueryBuilder()->select('c')->from(self::CATEGORY, 'c')->getQuery()->execute();

        $firstResult = $result[0];

        static::assertCount(1, $result);
        static::assertEquals('4', $firstResult->getTitle());
        static::assertEquals(1, $firstResult->getLevel());
        static::assertEquals('4-1', $firstResult->getTreeRootValue());
    }

    /**
     * @test
     */
    public function useOfSeparatorInPathSourceShouldThrowAnException()
    {
        $this->expectException(RuntimeException::class);

        $category = $this->createCategory();
        $category->setTitle('1'.$this->config['path_separator']);

        $this->em->persist($category);
        $this->em->flush();
    }

    public function createCategory()
    {
        $class = self::CATEGORY;

        return new $class();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    public function generatePath(array $sources)
    {
        $path = '';

        foreach ($sources as $p => $id) {
            $path .= $p.'-'.$id.$this->config['path_separator'];
        }

        return $path;
    }
}

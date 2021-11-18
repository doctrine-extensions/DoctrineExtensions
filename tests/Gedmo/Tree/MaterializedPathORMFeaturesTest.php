<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\MPFeaturesCategory;
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
final class MaterializedPathORMFeaturesTest extends BaseTestCaseORM
{
    public const CATEGORY = MPFeaturesCategory::class;

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
    public function checkPathsAndHash()
    {
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

        static::assertSame($this->generatePath(['1' => $category->getId()]), $category->getPath());
        static::assertSame($this->generatePath(['1' => $category->getId(), '2' => $category2->getId()]), $category2->getPath());
        static::assertSame($this->generatePath(['1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        static::assertSame($this->generatePath(['4' => $category4->getId()]), $category4->getPath());

        static::assertSame($this->generatePathHash(['1' => $category->getId()]), $category->getPathHash());
        static::assertSame($this->generatePathHash(['1' => $category->getId(), '2' => $category2->getId()]), $category2->getPathHash());
        static::assertSame($this->generatePathHash(['1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPathHash());
        static::assertSame($this->generatePathHash(['4' => $category4->getId()]), $category4->getPathHash());

        static::assertSame($category->getTitle(), $category->getTreeRootValue());
        static::assertSame($category->getTitle(), $category2->getTreeRootValue());
        static::assertSame($category->getTitle(), $category3->getTreeRootValue());
        static::assertSame($category4->getTitle(), $category4->getTreeRootValue());
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
            $path .= $this->config['path_separator'].$p;
        }

        return $path;
    }

    public function generatePathHash(array $sources)
    {
        return md5($this->generatePath($sources));
    }
}

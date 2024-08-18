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
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\MPFeaturesCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathORMFeaturesTest extends BaseTestCaseORM
{
    /**
     * @var array<string, mixed>
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

        $this->getDefaultMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(MPFeaturesCategory::class);
        $this->config = $this->listener->getConfiguration($this->em, $meta->getName());
    }

    public function testCheckPathsAndHash(): void
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            MPFeaturesCategory::class,
        ];
    }

    private function createCategory(): MPFeaturesCategory
    {
        $class = MPFeaturesCategory::class;

        return new $class();
    }

    /**
     * @param array<int|string, int|string|null> $sources
     */
    private function generatePath(array $sources): string
    {
        $path = '';
        foreach ($sources as $p => $id) {
            $path .= $this->config['path_separator'].$p;
        }

        return $path;
    }

    /**
     * @param array<int|string, int|string|null> $sources
     */
    private function generatePathHash(array $sources): string
    {
        return md5($this->generatePath($sources));
    }
}

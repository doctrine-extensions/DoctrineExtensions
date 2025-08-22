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
use Gedmo\Tests\Tree\Fixture\MPCategoryUuid;
use Gedmo\Tree\TreeListener;
use Symfony\Component\Uid\UuidV4;

/**
 * These are tests for Tree behavior when using Uuid as primary key and TreePathSource.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Andrea Bergamasco <a.bergamasco@keesystem.com>
 */
final class MaterializedPathUuidORMTest extends BaseTestCaseORM
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    protected TreeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(MPCategoryUuid::class);
        $this->config = $this->listener->getConfiguration($this->em, $meta->getName());
    }

    public function testInsertUpdateAndRemove(): void
    {
        // Insert
        $category = $this->createCategory();
        $category->setTitle('1');
        static::assertNotNull($category->getId());
        $category2 = $this->createCategory();
        $category2->setTitle('2');
        static::assertNotNull($category2->getId());
        $category3 = $this->createCategory();
        $category3->setTitle('3');
        static::assertNotNull($category3->getId());
        $category4 = $this->createCategory();
        $category4->setTitle('4');
        static::assertNotNull($category4->getId());

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

        static::assertSame($this->generatePath([$category->getId()]), $category->getPath());
        static::assertSame($this->generatePath([$category->getId(), $category2->getId()]), $category2->getPath());
        static::assertSame($this->generatePath([$category->getId(), $category2->getId(), $category3->getId()]), $category3->getPath());
        static::assertSame($this->generatePath([$category4->getId()]), $category4->getPath());
        static::assertSame(1, $category->getLevel());
        static::assertSame(2, $category2->getLevel());
        static::assertSame(3, $category3->getLevel());
        static::assertSame(1, $category4->getLevel());

        static::assertSame($this->getTreeRootValueOfRootNode($category), $category->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category2), $category2->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category3), $category3->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category4), $category4->getTreeRootValue());

        // Update
        $category2->setParent(null);

        $this->em->persist($category2);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);

        static::assertSame($this->generatePath([$category->getId()]), $category->getPath());
        static::assertSame($this->generatePath([$category2->getId()]), $category2->getPath());
        static::assertSame($this->generatePath([$category2->getId(), $category3->getId()]), $category3->getPath());
        static::assertSame(1, $category->getLevel());
        static::assertSame(1, $category2->getLevel());
        static::assertSame(2, $category3->getLevel());
        static::assertSame(1, $category4->getLevel());

        static::assertSame($this->getTreeRootValueOfRootNode($category), $category->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category2), $category2->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category3), $category3->getTreeRootValue());
        static::assertSame($this->getTreeRootValueOfRootNode($category4), $category4->getTreeRootValue());

        // Remove
        $this->em->remove($category);
        $this->em->remove($category2);
        $this->em->flush();

        $result = $this->em->createQueryBuilder()->select('c')->from(MPCategoryUuid::class, 'c')->getQuery()->getResult();

        $firstResult = $result[0];

        static::assertCount(1, $result);
        static::assertSame('4', $firstResult->getTitle());
        static::assertSame(1, $firstResult->getLevel());
        static::assertSame($this->getTreeRootValueOfRootNode($firstResult), $firstResult->getTreeRootValue());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            MPCategoryUuid::class,
        ];
    }

    private function createCategory(): MPCategoryUuid
    {
        return new MPCategoryUuid();
    }

    /**
     * @param array<int|string, int|string|UuidV4|null> $sources
     */
    private function generatePath(array $sources): string
    {
        $path = $this->config['path_starts_with_separator']
            ? $this->config['path_separator']
            : '';

        $path .= implode($this->config['path_separator'], $sources);

        $path .= $this->config['path_ends_with_separator']
            ? $this->config['path_separator']
            : '';

        return $path;
    }

    private function getTreeRootValueOfRootNode(MPCategoryUuid $category): string
    {
        while (null !== $category->getParent()) {
            $category = $category->getParent();
        }

        return $category->getTreeRootValue();
    }
}

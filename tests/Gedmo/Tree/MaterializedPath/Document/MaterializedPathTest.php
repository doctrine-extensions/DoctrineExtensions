<?php

namespace Gedmo\Tree\MaterializedPath\Document;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;

class MaterializedPathTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\MaterializedPath\Document\Category";

    private $config, $dm, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListener);

        $this->dm = $this->createDocumentManager($evm);

        $meta = $this->dm->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->dm, $meta->name)->getMapping();
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function insertUpdateAndRemove()
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

        $this->assertSame($this->generatePath(array('1' => $category->getId())), $category->getPath());
        $this->assertSame($this->generatePath(array('1' => $category->getId(), '2' => $category2->getId())), $category2->getPath());
        $this->assertSame($this->generatePath(array('1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId())), $category3->getPath());
        $this->assertSame($this->generatePath(array('4' => $category4->getId())), $category4->getPath());
        $this->assertSame(1, $category->getLevel());
        $this->assertSame(2, $category2->getLevel());
        $this->assertSame(3, $category3->getLevel());
        $this->assertSame(1, $category4->getLevel());

        // Update
        $category2->setParent(null);

        $this->dm->persist($category2);
        $this->dm->flush();

        $this->dm->refresh($category);
        $this->dm->refresh($category2);
        $this->dm->refresh($category3);

        $this->assertSame($this->generatePath(array('1' => $category->getId())), $category->getPath());
        $this->assertSame($this->generatePath(array('2' => $category2->getId())), $category2->getPath());
        $this->assertSame($this->generatePath(array('2' => $category2->getId(), '3' => $category3->getId())), $category3->getPath());
        $this->assertSame(1, $category->getLevel());
        $this->assertSame(1, $category2->getLevel());
        $this->assertSame(2, $category3->getLevel());
        $this->assertSame(1, $category4->getLevel());

        // Remove
        $this->dm->remove($category);
        $this->dm->remove($category2);
        $this->dm->flush();

        $result = $this->dm->createQueryBuilder()->find(self::CATEGORY)->getQuery()->execute();

        $firstResult = $result->getNext();

        $this->assertSame(1, $result->count());
        $this->assertSame('4', $firstResult->getTitle());
        $this->assertSame(1, $firstResult->getLevel());
    }

    /**
     * @test
     */
    public function useOfSeparatorInPathSourceShouldThrowAnException()
    {
        $this->setExpectedException('Gedmo\Exception\RuntimeException');

        $category = $this->createCategory();
        $category->setTitle('1'.$this->config['path_separator']);

        $this->dm->persist($category);
        $this->dm->flush();
    }

    public function createCategory()
    {
        $class = self::CATEGORY;
        return new $class;
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

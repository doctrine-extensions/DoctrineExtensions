<?php

namespace Gedmo\Tree\MaterializedPath;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;

class MaterializedPathTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\MaterializedPath\MPCategory";

    private $config, $em, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::CATEGORY,
        ));
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name)->getMapping();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
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

        $this->em->persist($category4);
        $this->em->persist($category3);
        $this->em->persist($category2);
        $this->em->persist($category);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);
        $this->em->refresh($category4);

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

        $this->em->persist($category2);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);

        $this->assertSame($this->generatePath(array('1' => $category->getId())), $category->getPath());
        $this->assertSame($this->generatePath(array('2' => $category2->getId())), $category2->getPath());
        $this->assertSame($this->generatePath(array('2' => $category2->getId(), '3' => $category3->getId())), $category3->getPath());
        $this->assertSame(1, $category->getLevel());
        $this->assertSame(1, $category2->getLevel());
        $this->assertSame(2, $category3->getLevel());
        $this->assertSame(1, $category4->getLevel());

        // Remove
        $this->em->remove($category);
        $this->em->remove($category2);
        $this->em->flush();

        $result = $this->em->createQueryBuilder()->select('c')->from(self::CATEGORY, 'c')->getQuery()->execute();

        $firstResult = $result[0];

        $this->assertCount(1, $result);
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

        $this->em->persist($category);
        $this->em->flush();
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

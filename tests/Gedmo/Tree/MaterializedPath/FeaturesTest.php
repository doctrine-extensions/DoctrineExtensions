<?php

namespace Gedmo\Tree\MaterializedPath;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;

class FeaturesTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\MaterializedPath\MPFeaturesCategory";

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
    function checkPathsAndHash()
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

        $this->assertSame($this->generatePath(array('1' => $category->getId())), $category->getPath());
        $this->assertSame($this->generatePath(array('1' => $category->getId(), '2' => $category2->getId())), $category2->getPath());
        $this->assertSame($this->generatePath(array('1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId())), $category3->getPath());
        $this->assertSame($this->generatePath(array('4' => $category4->getId())), $category4->getPath());
        $this->assertSame($this->generatePathHash(array('1' => $category->getId())), $category->getPathHash());
        $this->assertSame($this->generatePathHash(array('1' => $category->getId(), '2' => $category2->getId())), $category2->getPathHash());
        $this->assertSame($this->generatePathHash(array('1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId())), $category3->getPathHash());
        $this->assertSame($this->generatePathHash(array('4' => $category4->getId())), $category4->getPathHash());
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
            $path .= $this->config['path_separator'] . $p;
        }
        return $path;
    }

    public function generatePathHash(array $sources)
    {
        return md5($this->generatePath($sources));
    }
}

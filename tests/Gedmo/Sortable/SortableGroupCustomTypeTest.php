<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Sortable\Fixture\CustomType\Author;
use Sortable\Fixture\CustomType\Paper;
use Tool\BaseTestCaseORM;


class SortableGroupCustomTypeTest extends BaseTestCaseORM
{
    protected function getUsedEntityFixtures()
    {
        return array(
            'Sortable\Fixture\CustomType\Paper',
            'Sortable\Fixture\CustomType\Author',
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());
        require_once 'CustomType.php';
        Type::addType('mytype',  'Gedmo\Sortable\CustomType');

        $this->getMockSqliteEntityManager($evm);


        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('db_mytype', 'mytype');
    }


    /**
     * @test
     */
    public function shouldBeAbleToSort()
    {

        $p = new Paper();
        $p->setName('mypaper');
        $p->setId('a003');
        $this->em->persist($p);

        for ($i = 0; $i < 10; $i++) {
            $a = new Author();
            $a->setName('a' . $i);
            $a->setPaper($p);
            $this->em->persist($a);
        }
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository('Sortable\Fixture\CustomType\Author');

        $one = $repo->findOneBy(array('name' => 'a7'));
        $one->setPosition(4);
        $this->em->persist($one);
        $this->em->flush();

        $this->assertEquals(4,$one->getPosition());

    }

}

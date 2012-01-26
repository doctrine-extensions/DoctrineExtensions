<?php

namespace Gedmo\SoftDeleteable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\Debug,
    SoftDeleteable\Fixture\Entity\Article,
    Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.SoftDeleteable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableEntityTest extends BaseTestCaseORM
{
    const ARTICLE_CLASS = 'SoftDeleteable\Fixture\Entity\Article';

    private $softDeleteableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
    }

    public function testSoftDeleteable()
    {
        $art0 = new Article();
        $art0->setTitle('Title 1');

        $art1 = new Article();
        $art1->setTitle('Title 2');

        $this->em->persist($art0);
        $this->em->persist($art1);

        $this->em->flush();
        
        $meta = $this->em->getClassMetadata(self::ARTICLE_CLASS);

        $this->assertArrayHasKey('deletedAt', $meta->fieldNames);

        
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE
        );
    }

    private function populate()
    {
        
    }
}
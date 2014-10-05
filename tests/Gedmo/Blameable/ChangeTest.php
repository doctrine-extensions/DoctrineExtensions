<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Blameable\Fixture\Entity\TitledArticle;

/**
 * These are tests for Blameable behavior
 *
 * @author Ivan Borzenkov <ivan.borzenkov@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ChangeTest extends BaseTestCaseORM
{
    const FIXTURE = "Blameable\\Fixture\\Entity\\TitledArticle";

    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->listener = new BlameableListener();
        $this->listener->setUserValue('testuser');
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testChange()
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('Test');
        $test->setTitle('New Title');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Changed
        $this->assertEquals('testuser', $test->getChtitle());

        $this->listener->setUserValue('otheruser');

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('New Title');
        $test->setText('New Text');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Not Changed
        $this->assertEquals('testuser', $test->getChtitle());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
        );
    }
}

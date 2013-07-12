<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;
use Timestampable\Fixture\SupperClassExtension;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ProtectedPropertySupperclassTest extends BaseTestCaseORM
{
    const SUPERCLASS = "Timestampable\\Fixture\\SupperClassExtension";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testProtectedProperty()
    {
        $test = new SupperClassExtension;
        $test->setName('name');
        $test->setTitle('title');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertNotNull($test->getCreatedAt());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::SUPERCLASS
        );
    }
}

<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Document\Log;
use Gedmo\Exception\InvalidArgumentException;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Blameable\Fixture\Model\User;

/**
 * These are tests for Blameable behavior in hybrid databases
 *
 * User is an entity, blameable is a document
 *
 * @see Gedmo\Blameable\BlameableHybridTest
 */
class BlameableHybridTest extends BaseTestCaseMongoODM
{
    const TEST_USERNAME = 'testuser';
    const TEST_USERID = 1;

    const LOG = 'Blameable\Fixture\Document\Log';

    protected function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->setId(self::TEST_USERID);
        $user->setUsername(self::TEST_USERNAME);

        $listener = new BlameableListener();
        $listener->setUserValue($user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $manager = $this->getMockDocumentManager($evm);

        $this->populate();
        $manager->flush();
    }

    public function testBlameable()
    {
        $repo = $this->dm->getRepository(self::LOG);
        /** @var Log $log */
        $log = $repo->findOneByContent('Some log info');

        $this->assertEquals(self::TEST_USERNAME, $log->getCreated());
        $this->assertEquals(self::TEST_USERID, $log->getUpdated());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Field expects int, user must be an int, or object should have method getId, which returns an int.
     */
    public function testBlameableNoGetId()
    {
        $listener = new BlameableListener();
        $listener->setUserValue(new \stdClass());

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockDocumentManager($evm);

        $this->populate();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Field expects int, user must be an int, or object should have method getId, which returns an int.
     */
    public function testBlameableGetIdNotInt()
    {
        $user = new User();
        $user->setId('Not an int');
        $user->setUsername(self::TEST_USERNAME);

        $listener = new BlameableListener();
        $listener->setUserValue($user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockDocumentManager($evm);

        $this->populate();
    }

    private function populate()
    {
        $log = new Log();
        $log->setContent('Some log info');

        $this->dm->persist($log);
    }
}

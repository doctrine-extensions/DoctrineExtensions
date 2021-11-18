<?php

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\WithoutInterface;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class NoInterfaceTest extends BaseTestCaseORM
{
    public const FIXTURE = WithoutInterface::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $blameableListener = new BlameableListener();
        $blameableListener->setUserValue('testuser');
        $evm->addEventSubscriber($blameableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testBlameableNoInterface()
    {
        $test = new WithoutInterface();
        $test->setTitle('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'Test']);
        static::assertSame('testuser', $test->getCreated());
        static::assertSame('testuser', $test->getUpdated());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::FIXTURE,
        ];
    }
}

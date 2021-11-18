<?php

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\WithoutInterface;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior
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
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testTimestampableNoInterface(): void
    {
        $test = new WithoutInterface();
        $test->setTitle('Test');

        $date = new \DateTime('now');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'Test']);
        static::assertSame(
            $date->format('Y-m-d 00:00:00'),
            $test->getCreated()->format('Y-m-d H:i:s')
        );
        static::assertSame(
            $date->format('Y-m-d H:i'),
            $test->getUpdated()->format('Y-m-d H:i')
        );
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::FIXTURE,
        ];
    }
}

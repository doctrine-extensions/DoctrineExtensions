<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Tests\IpTraceable\Fixture\TitledArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class ChangeTest extends BaseTestCaseORM
{
    public const TEST_IP = '34.234.1.10';
    public const FIXTURE = TitledArticle::class;

    /**
     * @var IpTraceableListener
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new IpTraceableListener();
        $this->listener->setIpValue(self::TEST_IP);

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testChange(): void
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'Test']);
        $test->setTitle('New Title');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        // Changed
        static::assertSame(self::TEST_IP, $test->getChtitle());

        $this->listener->setIpValue('127.0.0.1');

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'New Title']);
        $test->setText('New Text');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        // Not Changed
        static::assertSame(self::TEST_IP, $test->getChtitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::FIXTURE,
        ];
    }
}

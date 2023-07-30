<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\Attribute\TitledArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Timestampable behavior
 *
 * @author Ivan Borzenkov <ivan.borzenkov@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @requires PHP >= 8.0
 */
final class AttributeChangeTest extends BaseTestCaseORM
{
    private const FIXTURE = TitledArticle::class;

    /**
     * @var TimestampableListenerStub
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TimestampableListenerStub();
        $this->listener->eventAdapter = new EventAdapterORMStub();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testChange(): void
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');
        $test->setState('Open');

        $currentDate = new \DateTime();
        $this->listener->eventAdapter->setDateValue($currentDate);

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'Test']);
        $test->setTitle('New Title');
        $test->setState('Closed');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        // Changed.
        static::assertSame(
            $currentDate->format('Y-m-d H:i:s'),
            $test->getChtitle()->format('Y-m-d H:i:s')
        );
        static::assertSame(
            $currentDate->format('Y-m-d H:i:s'),
            $test->getClosed()->format('Y-m-d H:i:s')
        );

        $anotherDate = \DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00');
        $this->listener->eventAdapter->setDateValue($anotherDate);

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'New Title']);
        $test->setText('New Text');
        $test->setState('Open');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        // Not Changed.
        static::assertSame(
            $currentDate->format('Y-m-d H:i:s'),
            $test->getChtitle()->format('Y-m-d H:i:s')
        );
        static::assertSame(
            $currentDate->format('Y-m-d H:i:s'),
            $test->getClosed()->format('Y-m-d H:i:s')
        );

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'New Title']);
        $test->setState('Published');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        // Changed.
        static::assertSame(
            $anotherDate->format('Y-m-d H:i:s'),
            $test->getClosed()->format('Y-m-d H:i:s')
        );
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::FIXTURE,
        ];
    }
}

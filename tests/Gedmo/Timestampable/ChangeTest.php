<?php

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Tests\Timestampable\Fixture\TitledArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior
 *
 * @author Ivan Borzenkov <ivan.borzenkov@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ChangeTest extends BaseTestCaseORM
{
    public const FIXTURE = TitledArticle::class;

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

    public function testChange()
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');
        $test->setState('Open');

        $currentDate = new \DateTime('now');
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
        //Changed
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
        //Not Changed
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
        //Changed
        static::assertSame(
            $anotherDate->format('Y-m-d H:i:s'),
            $test->getClosed()->format('Y-m-d H:i:s')
        );
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::FIXTURE,
        ];
    }
}

class EventAdapterORMStub extends BaseAdapterORM implements TimestampableAdapter
{
    protected $dateTime;

    public function setDateValue(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function getDateValue($meta, $field)
    {
        return $this->dateTime;
    }
}

class TimestampableListenerStub extends TimestampableListener
{
    public $eventAdapter;

    protected function getEventAdapter(EventArgs $args)
    {
        $this->eventAdapter->setEventArgs($args);

        return $this->eventAdapter;
    }
}

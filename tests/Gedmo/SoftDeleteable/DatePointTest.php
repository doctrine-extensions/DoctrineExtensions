<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Carbon\Doctrine\DateTimeType;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\DBAL\Types\Types;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\BookDatePoint;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Symfony\Component\Clock\DatePoint;

final class DatePointTest extends BaseTestCaseORM
{
    private const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    private SoftDeleteableListener $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        DoctrineType::overrideType(Types::DATETIME_MUTABLE, DateTimeType::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DoctrineType::overrideType(Types::DATETIME_MUTABLE, \Doctrine\DBAL\Types\DateTimeType::class);
    }

    public function testSoftDeleteable(): void
    {
        $repo = $this->em->getRepository(BookDatePoint::class);

        $book0 = new BookDatePoint();
        $field = 'title';
        $value = 'Title 1';
        $book0->setTitle($value);

        $this->em->persist($book0);
        $this->em->flush();

        static::assertNull($book0->getDeletedAt());

        $book = $repo->findOneBy([$field => $value]);
        $this->em->remove($book);
        $this->em->flush();

        $book = $repo->findOneBy([$field => $value]);
        static::assertNull($book);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $book = $repo->findOneBy([$field => $value]);
        static::assertIsObject($book);
        static::assertInstanceOf(DatePoint::class, $book->getDeletedAt());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            BookDatePoint::class,
        ];
    }
}

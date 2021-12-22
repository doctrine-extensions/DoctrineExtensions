<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\Tests\Sluggable\Fixture\Article;
use Gedmo\Tests\Sluggable\Fixture\Doctrine\FakeFilter;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Florian Vilpoix <florianv@gmail.com>
 */
final class SluggableFltersTest extends BaseTestCaseORM
{
    public const TARGET = Article::class;

    public const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    public const FAKE_FILTER_NAME = 'fake-filter';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $sluggableListener = new SluggableListener();
        $sluggableListener->addManagedFilter(self::SOFT_DELETEABLE_FILTER_NAME, true);
        $sluggableListener->addManagedFilter(self::FAKE_FILTER_NAME, true);
        $evm->addEventSubscriber($sluggableListener);

        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
        $config->addFilter(self::FAKE_FILTER_NAME, FakeFilter::class);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->getFilters()->enable(self::FAKE_FILTER_NAME);
    }

    public function testShouldSuccessWhenManagedFilterHasAlreadyBeenDisabled(): void
    {
        // disable one managed doctrine filter
        $this->em->getFilters()->disable(self::FAKE_FILTER_NAME);

        $slug = new Article();
        $slug->setCode('My code');
        $slug->setTitle('My title');

        $this->em->persist($slug);
        $this->em->flush();

        static::assertSame('my-title-my-code', $slug->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}

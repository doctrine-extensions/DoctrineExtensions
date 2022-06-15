<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue449\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Craig Marvelley <craig.marvelley@gmail.com>
 *
 * @see http://marvelley.com
 */
final class Issue449Test extends BaseTestCaseORM
{
    public const TARGET = Article::class;
    public const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    /**
     * @var SoftDeleteableListener
     */
    private $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $sluggableListener = new SluggableListener();
        $sluggableListener->addManagedFilter(self::SOFT_DELETEABLE_FILTER_NAME, true);
        $evm->addEventSubscriber($sluggableListener);

        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);

        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testShouldBuildUniqueSlugAfterSoftDeleteFilterIsDisabled(): void
    {
        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();

        $slug = $article->getSlug();

        $this->em->remove($article);
        $this->em->flush();

        $article = new Article();
        $article->setTitle('the soft title');
        $article->setCode('my soft code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        static::assertNotSame($slug, $article->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}

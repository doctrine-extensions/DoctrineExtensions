<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Document\User;
use Gedmo\Tests\SoftDeleteable\Fixture\Document\UserTimeAware;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 */
final class SoftDeleteableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\Article';
    public const COMMENT_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\Comment';
    public const PAGE_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\Page';
    public const MEGA_PAGE_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\MegaPage';
    public const MODULE_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\Module';
    public const OTHER_ARTICLE_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\OtherArticle';
    public const OTHER_COMMENT_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\OtherComment';
    public const USER_CLASS = User::class;
    public const USER__TIME_AWARE_CLASS = UserTimeAware::class;
    public const MAPPED_SUPERCLASS_CHILD_CLASS = 'Gedmo\Tests\SoftDeleteable\Fixture\Document\Child';
    public const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    /**
     * @var SoftDeleteableListener
     */
    private $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);

        $this->dm = $this->getMockDocumentManager($evm, $config);
        $this->dm->getFilterCollection()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testShouldSoftlyDeleteIfColumnNameDifferFromPropertyName(): void
    {
        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();

        $username = 'test_user';
        $newUser->setUsername($username);

        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());

        $this->dm->remove($user);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user);
    }

    /**
     * Tests the filter by enabling and disabling it between
     * some user persists actions.
     */
    public function testSoftDeleteableFilter(): void
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETEABLE_FILTER_NAME);
        static::assertInstanceOf(SoftDeleteableFilter::class, $filter);
        $filter->disableForDocument(self::USER_CLASS);

        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());
        $this->dm->remove($user);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNotNull($user->getDeletedAt());

        $filter->enableForDocument(self::USER_CLASS);

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user);
    }

    /**
     * Tests the filter with time aware option by enabling and disabling it between
     * some user persists actions.
     *
     * @TODO: not supported in ODM yet
     * test
     */
    public function shouldSupportSoftDeleteableFilterTimeAware(): void
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETEABLE_FILTER_NAME);
        static::assertInstanceOf(SoftDeleteableFilter::class, $filter);
        $filter->disableForDocument(self::USER__TIME_AWARE_CLASS);

        $repo = $this->dm->getRepository(self::USER__TIME_AWARE_CLASS);

        // Find entity with deletedAt date in future
        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $newUser->setDeletedAt(new \DateTime('tomorrow'));
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        $this->dm->remove($user);
        $this->dm->flush();

        // Don't find entity with deletedAt date in past
        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $newUser->setDeletedAt(new \DateTime('yesterday'));
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user);
        $this->dm->flush();
    }

    public function testPostSoftDeleteEventIsDispatched(): void
    {
        $subscriber = $this->getMockBuilder(EventSubscriber::class)
            ->setMethods([
                'getSubscribedEvents',
                'preSoftDelete',
                'postSoftDelete',
            ])
            ->getMock();

        $subscriber->expects(static::once())
            ->method('getSubscribedEvents')
            ->willReturn([SoftDeleteableListener::PRE_SOFT_DELETE, SoftDeleteableListener::POST_SOFT_DELETE]);

        $subscriber->expects(static::once())
            ->method('preSoftDelete')
            ->with(static::anything());

        $subscriber->expects(static::once())
            ->method('postSoftDelete')
            ->with(static::anything());

        $this->dm->getEventManager()->addEventSubscriber($subscriber);

        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(['username' => 'test_user']);

        static::assertNull($user->getDeletedAt());

        $this->dm->remove($user);
        $this->dm->flush();
    }
}

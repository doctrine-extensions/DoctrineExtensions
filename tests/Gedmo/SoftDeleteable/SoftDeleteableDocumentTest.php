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
use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Document\User;
use Gedmo\Tests\SoftDeleteable\Fixture\Document\UserTimeAware;
use Gedmo\Tests\SoftDeleteable\Fixture\Listener\WithLifecycleEventArgsFromODMTypeListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Listener\WithoutTypeListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Listener\WithPreAndPostSoftDeleteEventArgsTypeListener;
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

        $this->dm = $this->getMockDocumentManager($evm, $config);
        $this->dm->getFilterCollection()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testShouldSoftlyDeleteIfColumnNameDifferFromPropertyName(): void
    {
        $repo = $this->dm->getRepository(User::class);

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
        $filter->disableForDocument(User::class);

        $repo = $this->dm->getRepository(User::class);

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

        $filter->enableForDocument(User::class);

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
        $filter->disableForDocument(UserTimeAware::class);

        $repo = $this->dm->getRepository(UserTimeAware::class);

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
        $this->dm->getEventManager()->addEventSubscriber(new WithPreAndPostSoftDeleteEventArgsTypeListener());

        $this->doTestPostSoftDeleteEventIsDispatched();
    }

    /** @group legacy */
    public function testPostSoftDeleteEventIsDispatchedWithDeprecatedListeners(): void
    {
        $this->dm->getEventManager()->addEventSubscriber(new WithoutTypeListener());
        $this->dm->getEventManager()->addEventSubscriber(new WithLifecycleEventArgsFromODMTypeListener());

        $this->doTestPostSoftDeleteEventIsDispatched();
    }

    private function doTestPostSoftDeleteEventIsDispatched(): void
    {
        $repo = $this->dm->getRepository(User::class);

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

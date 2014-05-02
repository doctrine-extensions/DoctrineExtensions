<?php

namespace Gedmo\SoftDeletable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\Debug,
    SoftDeletable\Fixture\Document\Article,
    SoftDeletable\Fixture\Document\Comment,
    SoftDeletable\Fixture\Document\User,
    SoftDeletable\Fixture\Document\Page,
    SoftDeletable\Fixture\Document\MegaPage,
    SoftDeletable\Fixture\Document\Module,
    SoftDeletable\Fixture\Document\OtherArticle,
    SoftDeletable\Fixture\Document\OtherComment,
    SoftDeletable\Fixture\Document\Child,
    Gedmo\SoftDeletable\SoftDeletableListener;

/**
 * These are tests for SoftDeletable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeletableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE_CLASS = 'SoftDeletable\Fixture\Document\Article';
    const COMMENT_CLASS = 'SoftDeletable\Fixture\Document\Comment';
    const PAGE_CLASS = 'SoftDeletable\Fixture\Document\Page';
    const MEGA_PAGE_CLASS = 'SoftDeletable\Fixture\Document\MegaPage';
    const MODULE_CLASS = 'SoftDeletable\Fixture\Document\Module';
    const OTHER_ARTICLE_CLASS = 'SoftDeletable\Fixture\Document\OtherArticle';
    const OTHER_COMMENT_CLASS = 'SoftDeletable\Fixture\Document\OtherComment';
    const USER_CLASS = 'SoftDeletable\Fixture\Document\User';
    const USER__TIME_AWARE_CLASS = 'SoftDeletable\Fixture\Document\UserTimeAware';
    const MAPPED_SUPERCLASS_CHILD_CLASS = 'SoftDeletable\Fixture\Document\Child';
    const SOFT_DELETABLE_FILTER_NAME = 'soft-deletable';

    private $softDeletableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeletableListener = new SoftDeletableListener();
        $evm->addEventSubscriber($this->softDeletableListener);
        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETABLE_FILTER_NAME, 'Gedmo\SoftDeletable\Filter\ODM\SoftDeletableFilter');

        $this->dm = $this->getMockDocumentManager($evm, $config);
        $this->dm->getFilterCollection()->enable(self::SOFT_DELETABLE_FILTER_NAME);
    }

    /**
     * @test
     */
    public function shouldSoftlyDeleteIfColumnNameDifferFromPropertyName()
    {
        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();

        $username = 'test_user';
        $newUser->setUsername($username);

        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNull($user->getDeletedAt());

        $this->dm->remove($user);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNull($user);
    }
    /**
     * Tests the filter by enabling and disabling it between
     * some user persists actions.
     *
     * @test
     */
    public function testSoftDeletableFilter()
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETABLE_FILTER_NAME);
        $filter->disableForDocument(self::USER_CLASS);

        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNull($user->getDeletedAt());
        $this->dm->remove($user);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNotNull($user->getDeletedAt());

        $filter->enableForDocument(self::USER_CLASS);

        $user = $repo->findOneBy(array('username' => $username));
        $this->assertNull($user);
    }

    /**
     * Tests the filter with time aware option by enabling and disabling it between
     * some user persists actions.
     *
     * @TODO: not supported in ODM yet
     * test
     */
    public function shouldSupportSoftDeletableFilterTimeAware()
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETABLE_FILTER_NAME);
        $filter->disableForDocument(self::USER__TIME_AWARE_CLASS);

        $repo = $this->dm->getRepository(self::USER__TIME_AWARE_CLASS);

        //Find entity with deletedAt date in future
        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $newUser->setDeletedAt(new \DateTime('tomorrow'));
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->dm->remove($user);
        $this->dm->flush();

        //Don't find entity with deletedAt date in past
        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);
        $newUser->setDeletedAt(new \DateTime('yesterday'));
        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNull($user);
        $this->dm->remove($user);
        $this->dm->flush();

    }
    public function testPostSoftDeleteEventIsDispatched()
    {
        $subscriber = $this->getMock(
            "Doctrine\Common\EventSubscriber",
            array(
                "getSubscribedEvents",
                "preSoftDelete",
                "postSoftDelete"
            )
        );

        $subscriber->expects($this->once())
            ->method("getSubscribedEvents")
            ->will($this->returnValue(array(SoftDeletableListener::PRE_SOFT_DELETE, SoftDeletableListener::POST_SOFT_DELETE)));

        $subscriber->expects($this->once())
            ->method("preSoftDelete")
            ->with($this->anything());

        $subscriber->expects($this->once())
            ->method("postSoftDelete")
            ->with($this->anything());

        $this->dm->getEventManager()->addEventSubscriber($subscriber);

        $repo = $this->dm->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->dm->persist($newUser);
        $this->dm->flush();

        $user = $repo->findOneBy(array('username' => 'test_user'));

        $this->assertNull($user->getDeletedAt());

        $this->dm->remove($user);
        $this->dm->flush();
    }
}

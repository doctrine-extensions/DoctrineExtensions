<?php

namespace Gedmo\SoftDeleteable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use SoftDeleteable\Fixture\Document\Article;
use SoftDeleteable\Fixture\Document\Comment;
use SoftDeleteable\Fixture\Document\User;
use SoftDeleteable\Fixture\Document\Page;
use SoftDeleteable\Fixture\Document\MegaPage;
use SoftDeleteable\Fixture\Document\Module;
use SoftDeleteable\Fixture\Document\OtherArticle;
use SoftDeleteable\Fixture\Document\OtherComment;
use SoftDeleteable\Fixture\Document\Child;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE_CLASS = 'SoftDeleteable\Fixture\Document\Article';
    const COMMENT_CLASS = 'SoftDeleteable\Fixture\Document\Comment';
    const PAGE_CLASS = 'SoftDeleteable\Fixture\Document\Page';
    const MEGA_PAGE_CLASS = 'SoftDeleteable\Fixture\Document\MegaPage';
    const MODULE_CLASS = 'SoftDeleteable\Fixture\Document\Module';
    const OTHER_ARTICLE_CLASS = 'SoftDeleteable\Fixture\Document\OtherArticle';
    const OTHER_COMMENT_CLASS = 'SoftDeleteable\Fixture\Document\OtherComment';
    const USER_CLASS = 'SoftDeleteable\Fixture\Document\User';
    const USER__TIME_AWARE_CLASS = 'SoftDeleteable\Fixture\Document\UserTimeAware';
    const MAPPED_SUPERCLASS_CHILD_CLASS = 'SoftDeleteable\Fixture\Document\Child';
    const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    private $softDeleteableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, 'Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter');

        $this->dm = $this->getMockDocumentManager($evm, $config);
        $this->dm->getFilterCollection()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
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
    public function testSoftDeleteableFilter()
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETEABLE_FILTER_NAME);
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
    public function shouldSupportSoftDeleteableFilterTimeAware()
    {
        $filter = $this->dm->getFilterCollection()->getFilter(self::SOFT_DELETEABLE_FILTER_NAME);
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
                "postSoftDelete",
            )
        );

        $subscriber->expects($this->once())
            ->method("getSubscribedEvents")
            ->will($this->returnValue(array(SoftDeleteableListener::PRE_SOFT_DELETE, SoftDeleteableListener::POST_SOFT_DELETE)));

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

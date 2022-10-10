<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use function class_exists;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Article;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Child;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Comment;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\MegaPage;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Module;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\OtherArticle;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\OtherComment;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Page;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\User;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\UserNoHardDelete;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 */
final class SoftDeleteableEntityTest extends BaseTestCaseORM
{
    public const ARTICLE_CLASS = Article::class;
    public const COMMENT_CLASS = Comment::class;
    public const PAGE_CLASS = Page::class;
    public const MEGA_PAGE_CLASS = MegaPage::class;
    public const MODULE_CLASS = Module::class;
    public const OTHER_ARTICLE_CLASS = OtherArticle::class;
    public const OTHER_COMMENT_CLASS = OtherComment::class;
    public const USER_CLASS = User::class;
    public const MAPPED_SUPERCLASS_CHILD_CLASS = Child::class;
    public const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    public const USER_NO_HARD_DELETE_CLASS = UserNoHardDelete::class;

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
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testShouldBeAbleToHardDeleteSoftdeletedItems(): void
    {
        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $newUser->setUsername($username = 'test_user');

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user);
    }

    public function testShouldSoftlyDeleteIfColumnNameDifferFromPropertyName(): void
    {
        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user, 'User should be filtered out');

        // now deactivate filter and attempt to hard delete
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $user = $repo->findOneBy(['username' => $username]);
        static::assertNotNull($user, 'User should be fetched when filter is disabled');

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user, 'User is still available after hard delete');
    }

    public function testSoftDeleteable(): void
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        static::assertNull($art->getDeletedAt());
        static::assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertNull($comment);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf(\DateTime::class, $art->getDeletedAt());
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertIsObject($comment);
        static::assertIsObject($comment->getDeletedAt());
        static::assertInstanceOf(\DateTime::class, $comment->getDeletedAt());

        $this->em->createQuery('UPDATE '.self::ARTICLE_CLASS.' a SET a.deletedAt = NULL')->execute();

        $this->em->refresh($art);
        $this->em->refresh($comment);

        // Now we try with a DQL Delete query
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $dql = sprintf('DELETE FROM %s a WHERE a.%s = :%s',
            self::ARTICLE_CLASS, $field, $field);
        $query = $this->em->createQuery($dql);
        $query->setParameter($field, $value);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            SoftDeleteableWalker::class
        );

        $query->execute();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $art = $repo->findOneBy([$field => $value]);

        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf(\DateTime::class, $art->getDeletedAt());

        // Inheritance tree DELETE DQL
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $megaPageRepo = $this->em->getRepository(self::MEGA_PAGE_CLASS);
        $module = new Module();
        $module->setTitle('Module 1');
        $page = new MegaPage();
        $page->setTitle('Page 1');
        $page->addModule($module);
        $module->setPage($page);

        $this->em->persist($page);
        $this->em->persist($module);
        $this->em->flush();

        $dql = sprintf('DELETE FROM %s p',
            self::PAGE_CLASS);
        $query = $this->em->createQuery($dql);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            SoftDeleteableWalker::class
        );

        $query->execute();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);
        static::assertNull($p);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);

        static::assertIsObject($p);
        static::assertIsObject($p->getDeletedAt());
        static::assertInstanceOf(\DateTime::class, $p->getDeletedAt());

        // Test of #301
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $otherArticleRepo = $this->em->getRepository(self::OTHER_ARTICLE_CLASS);
        $otherCommentRepo = $this->em->getRepository(self::OTHER_COMMENT_CLASS);
        $otherArt = new OtherArticle();
        $otherComment = new OtherComment();
        $otherArt->setTitle('Page 1');
        $otherComment->setComment('Comment');
        $otherArt->addComment($otherComment);
        $otherComment->setArticle($otherArt);

        $this->em->persist($otherArt);
        $this->em->persist($otherComment);
        $this->em->flush();

        $this->em->refresh($otherArt);
        $this->em->refresh($otherComment);

        $artId = $otherArt->getId();
        $commentId = $otherComment->getId();

        $this->em->remove($otherArt);
        $this->em->flush();

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        static::assertNull($foundArt);
        static::assertIsObject($foundComment);
        static::assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);

        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        static::assertIsObject($foundArt);
        static::assertIsObject($foundArt->getDeletedAt());
        static::assertInstanceOf(\DateTime::class, $foundArt->getDeletedAt());
        static::assertIsObject($foundComment);
        static::assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);
    }

    /**
     * @group datetimeinterface
     */
    public function testSoftDeleteableWithDateTimeInterface(): void
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        static::assertNull($art->getDeletedAt());
        static::assertNull($comment->getDeletedAt());

        $art->setDeletedAt(new \DateTime());
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf('DateTimeInterface', $art->getDeletedAt());
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertIsObject($comment);
        static::assertNull($comment->getDeletedAt());

        $this->em->createQuery('UPDATE '.self::ARTICLE_CLASS.' a SET a.deletedAt = NULL')->execute();

        $this->em->refresh($art);
        $this->em->refresh($comment);

        // Now we try with a DQL Delete query
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $dql = sprintf('DELETE FROM %s a WHERE a.%s = :%s',
            self::ARTICLE_CLASS, $field, $field);
        $query = $this->em->createQuery($dql);
        $query->setParameter($field, $value);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            SoftDeleteableWalker::class
        );

        $query->execute();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $art = $repo->findOneBy([$field => $value]);

        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf('DateTimeInterface', $art->getDeletedAt());

        // Inheritance tree DELETE DQL
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $megaPageRepo = $this->em->getRepository(self::MEGA_PAGE_CLASS);
        $module = new Module();
        $module->setTitle('Module 1');
        $page = new MegaPage();
        $page->setTitle('Page 1');
        $page->addModule($module);
        $module->setPage($page);

        $this->em->persist($page);
        $this->em->persist($module);
        $this->em->flush();

        $dql = sprintf('DELETE FROM %s p',
            self::PAGE_CLASS);
        $query = $this->em->createQuery($dql);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            SoftDeleteableWalker::class
        );

        $query->execute();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);
        static::assertNull($p);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);

        static::assertIsObject($p);
        static::assertIsObject($p->getDeletedAt());
        static::assertInstanceOf('DateTimeInterface', $p->getDeletedAt());

        // Test of #301
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $otherArticleRepo = $this->em->getRepository(self::OTHER_ARTICLE_CLASS);
        $otherCommentRepo = $this->em->getRepository(self::OTHER_COMMENT_CLASS);
        $otherArt = new OtherArticle();
        $otherComment = new OtherComment();
        $otherArt->setTitle('Page 1');
        $otherComment->setComment('Comment');
        $otherArt->addComment($otherComment);
        $otherComment->setArticle($otherArt);

        $this->em->persist($otherArt);
        $this->em->persist($otherComment);
        $this->em->flush();

        $this->em->refresh($otherArt);
        $this->em->refresh($otherComment);

        $artId = $otherArt->getId();
        $commentId = $otherComment->getId();

        $otherArt->setDeletedAt(new \DateTime());
        $this->em->flush();

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        static::assertNull($foundArt);
        static::assertIsObject($foundComment);
        static::assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);

        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        static::assertIsObject($foundArt);
        static::assertIsObject($foundArt->getDeletedAt());
        static::assertInstanceOf('DateTimeInterface', $foundArt->getDeletedAt());
        static::assertIsObject($foundComment);
        static::assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);
    }

    /**
     * Make sure that soft delete also works when configured on a mapped superclass
     */
    public function testMappedSuperclass(): void
    {
        $child = new Child();
        $child->setTitle('test title');

        $this->em->persist($child);
        $this->em->flush();

        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::MAPPED_SUPERCLASS_CHILD_CLASS);
        static::assertNull($repo->findOneBy(['id' => $child->getId()]));

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        static::assertNotNull($repo->findById($child->getId()));
    }

    public function testSoftDeleteableFilter(): void
    {
        $filter = $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        static::assertInstanceOf(SoftDeleteableFilter::class, $filter);
        $filter->disableForEntity(self::USER_CLASS);

        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNotNull($user->getDeletedAt());

        $filter->enableForEntity(self::USER_CLASS);

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user);
    }

    public function testShouldFilterBeQueryCachedCorrectlyWhenToggledForEntity(): void
    {
        if (!class_exists(ArrayCache::class)) {
            static::markTestSkipped('Test only applies when doctrine/cache 1.x is installed');
        }

        $this->em->getConfiguration()->setQueryCache(CacheAdapter::wrap(new ArrayCache()));

        $filter = $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        static::assertInstanceOf(SoftDeleteableFilter::class, $filter);
        $filter->disableForEntity(self::USER_CLASS);

        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $dql = 'SELECT u FROM '.self::USER_CLASS.' u WHERE u.username = :username';
        $q = $this->em->createQuery($dql)
                      ->setParameter('username', $username)
        ;
        $data = $q->getResult();
        static::assertCount(1, $data);
        $user = $data[0];
        static::assertNotNull($user->getDeletedAt());

        $filter->enableForEntity(self::USER_CLASS);

        // The result should be different even with the query cache enabled.
        $q = $this->em->createQuery($dql)
                      ->setParameter('username', $username)
        ;
        $data = $q->getResult();
        static::assertCount(0, $data);
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
                   ->willReturn([
                       SoftDeleteableListener::PRE_SOFT_DELETE,
                       SoftDeleteableListener::POST_SOFT_DELETE,
                   ]);

        $subscriber->expects(static::exactly(2))
                   ->method('preSoftDelete')
                   ->with(static::anything());

        $subscriber->expects(static::exactly(2))
                   ->method('postSoftDelete')
                   ->with(static::anything());

        $this->em->getEventManager()->addEventSubscriber($subscriber);

        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        static::assertNull($art->getDeletedAt());
        static::assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();
    }

    public function testShouldNotDeleteIfColumnNameDifferFromPropertyName(): void
    {
        $repo = $this->em->getRepository(self::USER_NO_HARD_DELETE_CLASS);

        $newUser = new UserNoHardDelete();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        static::assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNull($user, 'User should be filtered out');

        // now deactivate filter and attempt to hard delete
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $user = $repo->findOneBy(['username' => $username]);
        static::assertNotNull($user, 'User should be fetched when filter is disabled');

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        static::assertNotNull($user, 'User is still available, hard delete done');
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE_CLASS,
            self::PAGE_CLASS,
            self::MEGA_PAGE_CLASS,
            self::MODULE_CLASS,
            self::COMMENT_CLASS,
            self::USER_CLASS,
            self::OTHER_ARTICLE_CLASS,
            self::OTHER_COMMENT_CLASS,
            self::MAPPED_SUPERCLASS_CHILD_CLASS,
            self::USER_NO_HARD_DELETE_CLASS,
        ];
    }
}

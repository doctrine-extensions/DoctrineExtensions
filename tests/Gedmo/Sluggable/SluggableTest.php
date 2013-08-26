<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Sluggable\Article;
use Gedmo\Sluggable\SluggableListener;

class SluggableTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Sluggable\Article';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldInsertNewSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }

    /**
     * @test
     */
    function shouldBuildUniqueSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->assertEquals($article->getSlug(), 'the-title-my-code-' . ($i + 1));
        }
    }

    /**
     * @test
     */
    function shouldHandleUniqueSlugLimitedLength()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        $long = 'the title the title the title the title the title the titlethetg';
        $article = new Article();
        $article->setTitle($long);
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $isPsql = $this->em->getConnection()->getDatabasePlatform()->getName() === 'postgresql';
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle($long);
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();

            $shorten = $article->getSlug();
            $this->assertSame($isPsql ? 63 : 64, strlen($shorten));
            $expected = 'the-title-the-title-the-title-the-title-the-title-the-titlethetg';
            $expected = substr($expected, 0, ($isPsql ? 63 : 64) - (strlen($i+1) + 1)) . '-' . ($i+1);
            $this->assertSame($shorten, $expected);
        }
    }

    /**
     * @test
     */
    function doubleDelimiterShouldBeRemoved()
    {
        $long = 'Sample long title which should be correctly slugged blablabla';
        $article = new Article();
        $article->setTitle($long);
        $article->setCode('my code');

        $article2 = new Article();
        $article2->setTitle($long);
        $article2->setCode('my code');

        $this->em->persist($article);
        $this->em->persist($article2);
        $this->em->flush();

        $isPsql = $this->em->getConnection()->getDatabasePlatform()->getName() === 'postgresql';
        if ($isPsql) {
            $this->assertSame("sample-long-title-which-should-be-correctly-slugged-blablabla-m", $article->getSlug());
        } else {
            $this->assertSame("sample-long-title-which-should-be-correctly-slugged-blablabla-my", $article->getSlug());
        }
        // OLD IMPLEMENTATION PRODUCE SLUG sample-long-title-which-should-be-correctly-slugged-blablabla--1
        $this->assertSame("sample-long-title-which-should-be-correctly-slugged-blablabla-1", $article2->getSlug());
    }

    /**
     * @test
     */
    function shouldHandleNumbersInSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code 123');

        $this->em->persist($article);
        $this->em->flush();
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code 123');

            $this->em->persist($article);
            $this->em->flush();
            $this->assertSame($article->getSlug(), 'the-title-my-code-123-' . ($i + 1));
        }
    }

    /**
     * @test
     */
    function shouldUpdateSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('the-title-my-code', $article->getSlug());

        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('the-title-updated-my-code', $article->getSlug());
    }

    /**
     * @test
     */
    function shouldBeAbleToForceRegenerationOfSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();

        $article->setSlug(null);
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('the-title-my-code', $article->getSlug());
    }

    /**
     * @test
     */
    function shouldBeAbleToForceTheSlug()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setSlug('my-cust-slug');

        $new = new Article;
        $new->setTitle('hey');
        $new->setCode('cc');
        $new->setSlug('forced');
        $this->em->persist($new);
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('my-cust-slug', $article->getSlug());

        $article->setSlug('my-forced-slug');
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('my-forced-slug', $article->getSlug());
        $this->assertSame('forced', $new->getSlug());
    }

    /**
     * @test
     */
    function shouldSolveGithubIssue45()
    {
        // persist new records with same slug
        $article = new Article;
        $article->setTitle('test');
        $article->setCode('code');
        $this->em->persist($article);

        $article2 = new Article;
        $article2->setTitle('test');
        $article2->setCode('code');
        $this->em->persist($article2);

        $this->em->flush();
        $this->assertSame('test-code', $article->getSlug());
        $this->assertSame('test-code-1', $article2->getSlug());
    }

    /**
     * @test
     */
    function shouldSolveGithubIssue57()
    {
        // slug matched by prefix
        $article = new Article;
        $article->setTitle('my');
        $article->setCode('slug');
        $this->em->persist($article);

        $article2 = new Article;
        $article2->setTitle('my');
        $article2->setCode('s');
        $this->em->persist($article2);

        $this->em->flush();
        $this->assertSame('my-s', $article2->getSlug());
    }

    /**
     * @test
     */
    function shouldAllowForcingEmptySlugAndRegenerateIfNullIssue807()
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setSlug('my-cust-slug');
        $this->em->persist($article);
        $this->em->flush();

        $article->setSlug('');
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('', $article->getSlug());

        $article->setSlug(null);
        $this->em->persist($article);
        $this->em->flush();

        $this->assertSame('the-title-my-code', $article->getSlug());

        $same = new Article;
        $same->setTitle('any');
        $same->setCode('any');
        $same->setSlug('the-title-my-code');
        $this->em->persist($same);
        $this->em->flush();

        $this->assertSame('the-title-my-code-1', $same->getSlug());
    }
}

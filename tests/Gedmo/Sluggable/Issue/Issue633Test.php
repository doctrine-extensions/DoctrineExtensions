<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Gedmo\Fixture\Sluggable\Issue633\Article;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Sluggable\SluggableListener;

/**
 * @author Derek Clapham <derek.clapham@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue633Test extends ObjectManagerTestCase
{
    const TARGET = 'Gedmo\Fixture\Sluggable\Issue633\Article';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::TARGET,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldHandleUniqueBasedSlug()
    {
        $test = new Article;
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());

        $test2 = new Article;
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test2->getSlug());

        $test3 = new Article;
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }

    /**
     * @test
     */
    function handlePersistedSlugsForUniqueBased()
    {
        $test = new Article;
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);

        $test2 = new Article;
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);

        $test3 = new Article;
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());
        $this->assertEquals('unique-to-code', $test2->getSlug());
        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }
}

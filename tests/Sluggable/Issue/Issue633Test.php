<?php

namespace Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Issue633\Article;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Derek Clapham <derek.clapham@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue633Test extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Issue633\Article';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

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
    public function shouldHandleUniqueBasedSlug()
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test2->getSlug());

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }

    /**
     * @test
     */
    public function handlePersistedSlugsForUniqueBased()
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());
        $this->assertEquals('unique-to-code', $test2->getSlug());
        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }
}

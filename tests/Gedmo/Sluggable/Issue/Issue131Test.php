<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Sluggable\Issue131\Article;
use Gedmo\Sluggable\SluggableListener;

class Issue131Test extends ObjectManagerTestCase
{
    const TARGET = 'Gedmo\Fixture\Sluggable\Issue131\Article';

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
    function shouldAllowNullableSlug()
    {
        $test = new Article;
        $test->setTitle('');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertNull($test->getSlug());

        $test2 = new Article;
        $test2->setTitle('');

        $this->em->persist($test2);
        $this->em->flush();

        $this->assertNull($test2->getSlug());
    }
}

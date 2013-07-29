<?php

namespace Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Issue131\Article;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue131Test extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Issue131\Article';

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

    public function testSlugGeneration()
    {
        $test = new Article();
        $test->setTitle('');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertNull($test->getSlug());

        $test2 = new Article();
        $test2->setTitle('');

        $this->em->persist($test2);
        $this->em->flush();

        $this->assertNull($test2->getSlug());
    }
}

<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue131\Article;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue131Test extends BaseTestCaseORM
{
    const TARGET = 'Sluggable\\Fixture\\Issue131\\Article';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
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

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
    }
}

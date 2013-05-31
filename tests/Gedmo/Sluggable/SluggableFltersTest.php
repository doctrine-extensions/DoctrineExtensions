<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Article;


/**
 * These are tests for Sluggable behavior
 *
 * @author Florian Vilpoix <florianv@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableFltersTest extends BaseTestCaseORM
{
    const TARGET = 'Sluggable\\Fixture\\Article';

    const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    const FAKE_FILTER_NAME = 'fake-filter';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $sluggableListener = new SluggableListener;
        $sluggableListener->addManagedFilter(self::SOFT_DELETEABLE_FILTER_NAME, true);
        $sluggableListener->addManagedFilter(self::FAKE_FILTER_NAME, true);
        $evm->addEventSubscriber($sluggableListener);

        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $config->addFilter(self::FAKE_FILTER_NAME, 'Sluggable\Fixture\Doctrine\FakeFilter');

        $this->em = $this->getMockSqliteEntityManager($evm, $config);

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->getFilters()->enable(self::FAKE_FILTER_NAME);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
    }

    /**
     * @test
     */
    function shouldSuccessWhenManagedFilterHasAlreadyBeenDisabled()
    {
        // disable one managed doctrine filter
        $this->em->getFilters()->disable(self::FAKE_FILTER_NAME);

        $slug = new Article;
        $slug->setCode('My code');
        $slug->setTitle('My title');

        $this->em->persist($slug);
        $this->em->flush();

        $this->assertEquals('my-title-my-code', $slug->getSlug());
    }
}

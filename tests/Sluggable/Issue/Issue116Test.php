<?php

namespace Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Fixture\Sluggable\Issue116\Country;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue116Test extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Issue116\Country';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl(new YamlDriver(array(__DIR__.'/../Fixture/Issue116/Mapping')));

        $this->em = $this->createEntityManager($evm, null, $config);
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
        $country = new Country();
        $country->setOriginalName('New Zealand');

        $this->em->persist($country);
        $this->em->flush();

        $this->assertEquals('new-zealand', $country->getAlias());
    }
}

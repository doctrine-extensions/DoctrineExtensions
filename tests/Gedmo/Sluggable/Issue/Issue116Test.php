<?php

namespace Gedmo\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Fixture\Sluggable\Issue116\Country;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\TestTool\ObjectManagerTestCase;

class Issue116Test extends ObjectManagerTestCase
{
    const TARGET = 'Gedmo\Fixture\Sluggable\Issue116\Country';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $driver = new YamlDriver(array($this->getTestsDir().'/Gedmo/Fixture/Sluggable/Issue116/Mapping'));

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->setMetadataDriverImpl($driver);
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
    public function shouldFixIssue116()
    {
        $country = new Country();
        $country->setOriginalName('New Zealand');

        $this->em->persist($country);
        $this->em->flush();

        $this->assertSame('new-zealand', $country->getAlias());
    }
}

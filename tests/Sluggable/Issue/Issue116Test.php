<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Sluggable\Issue116\Country;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

class Issue116Test extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Issue116\Country';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $driver = new YamlDriver(array($this->getTestsDir() . '/Fixture/Sluggable/Issue116/Mapping'));

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
    function shouldFixIssue116()
    {
        $country = new Country;
        $country->setOriginalName('New Zealand');

        $this->em->persist($country);
        $this->em->flush();

        $this->assertSame('new-zealand', $country->getAlias());
    }
}

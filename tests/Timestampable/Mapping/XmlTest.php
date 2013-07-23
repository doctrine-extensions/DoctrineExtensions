<?php

namespace Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Timestampable\TimestampableListener;

require_once __DIR__ . '/TimestampableMappingTestCase.php';

class XmlTest extends TimestampableMappingTestCase
{
    /**
     * @test
     */
    function shouldSupportXmlMapping()
    {
        $xmlDriver = new XmlDriver(__DIR__);

        $evm = new EventManager;
        $evm->addEventSubscriber($timestampable = new TimestampableListener);

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($xmlDriver);

        $meta = $em->getClassMetadata('Fixture\Timestampable\Mapping');
        $config = $timestampable->getConfiguration($em, $meta->name);

        $this->assertTimestampableMapping($config);
    }
}

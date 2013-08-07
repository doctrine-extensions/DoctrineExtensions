<?php

namespace Gedmo\Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Timestampable\TimestampableListener;

require_once __DIR__ . '/MappingTestCase.php';

class XmlTest extends MappingTestCase
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

        $meta = $em->getClassMetadata('Gedmo\Fixture\Timestampable\Mapping');
        $exm = $timestampable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

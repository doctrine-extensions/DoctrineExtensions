<?php

namespace Blameable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Blameable\BlameableListener;

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
        $evm->addEventSubscriber($blameable = new BlameableListener);
        $blameable->setUserValue('username');

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($xmlDriver);

        $meta = $em->getClassMetadata('Fixture\Blameable\Mapping');
        $exm = $blameable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

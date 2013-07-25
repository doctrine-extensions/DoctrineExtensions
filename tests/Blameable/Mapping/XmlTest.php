<?php

namespace Blameable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Blameable\BlameableListener;

require_once __DIR__ . '/BlameableMappingTestCase.php';

class XmlTest extends BlameableMappingTestCase
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
        $config = $blameable->getConfiguration($em, $meta->name);

        $this->assertBlameableMapping($config);
    }
}

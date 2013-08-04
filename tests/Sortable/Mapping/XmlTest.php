<?php

namespace Sortable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Sortable\SortableListener;

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
        $evm->addEventSubscriber($sortable = new SortableListener);

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($xmlDriver);

        $meta = $em->getClassMetadata('Fixture\Sortable\Mapping');
        $exm = $sortable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

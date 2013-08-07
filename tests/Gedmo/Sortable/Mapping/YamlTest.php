<?php

namespace Gedmo\Sortable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Sortable\SortableListener;

require_once __DIR__ . '/MappingTestCase.php';

class YamlTest extends MappingTestCase
{
    /**
     * @test
     */
    function shouldSupportYamlMapping()
    {
        $yamlDriver = new YamlDriver(__DIR__);

        $evm = new EventManager;
        $evm->addEventSubscriber($sortable = new SortableListener);

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);

        $meta = $em->getClassMetadata('Gedmo\Fixture\Sortable\Mapping');
        $exm = $sortable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

<?php

namespace Sortable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Sortable\SortableListener;

require_once __DIR__ . '/SortableMappingTestCase.php';

class YamlTest extends SortableMappingTestCase
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

        $meta = $em->getClassMetadata('Fixture\Sortable\Mapping');
        $config = $sortable->getConfiguration($em, $meta->name);

        $this->assertSortableMapping($config);
    }
}

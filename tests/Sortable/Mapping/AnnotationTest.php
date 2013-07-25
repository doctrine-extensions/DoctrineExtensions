<?php

namespace Sortable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Sortable\SortableListener;

require_once __DIR__ . '/SortableMappingTestCase.php';

class AnnotationTest extends SortableMappingTestCase
{
    /**
     * @test
     */
    function shouldMapSortableEntity()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($sortable = new SortableListener);
        $em = $this->createEntityManager($evm);

        $meta = $em->getClassMetadata('Fixture\Sortable\Mapping');
        $config = $sortable->getConfiguration($em, $meta->name);

        $this->assertSortableMapping($config);
    }
}


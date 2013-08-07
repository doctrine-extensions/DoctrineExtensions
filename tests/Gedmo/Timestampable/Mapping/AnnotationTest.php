<?php

namespace Gedmo\Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Timestampable\TimestampableListener;

require_once __DIR__ . '/MappingTestCase.php';

class AnnotationTest extends MappingTestCase
{
    /**
     * @test
     */
    function shouldMapAnnotatedEntity()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($timestampable = new TimestampableListener);
        $em = $this->createEntityManager($evm);

        $meta = $em->getClassMetadata('Gedmo\Fixture\Timestampable\Mapping');
        $exm = $timestampable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}


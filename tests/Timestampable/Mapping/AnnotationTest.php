<?php

namespace Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Timestampable\TimestampableListener;

require_once __DIR__ . '/TimestampableMappingTestCase.php';

class AnnotationTest extends TimestampableMappingTestCase
{
    /**
     * @test
     */
    function shouldMapTimestampableEntity()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($timestampable = new TimestampableListener);
        $em = $this->createEntityManager($evm);

        $meta = $em->getClassMetadata('Fixture\Timestampable\Mapping');
        $config = $timestampable->getConfiguration($em, $meta->name);

        $this->assertTimestampableMapping($config);
    }
}


<?php

namespace Blameable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;

require_once __DIR__ . '/BlameableMappingTestCase.php';

class AnnotationTest extends BlameableMappingTestCase
{
    /**
     * @test
     */
    function shouldMapBlameable()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($blameable = new BlameableListener);
        $blameable->setUserValue('username');

        $em = $this->createEntityManager($evm);

        $meta = $em->getClassMetadata('Fixture\Blameable\Mapping');
        $config = $blameable->getConfiguration($em, $meta->name);

        $this->assertBlameableMapping($config);
    }
}

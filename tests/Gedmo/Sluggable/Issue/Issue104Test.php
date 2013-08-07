<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Sluggable\SluggableListener;

class Issue104Test extends ObjectManagerTestCase
{
    const CAR = 'Gedmo\Fixture\Sluggable\Issue104\Car';

    /**
     * @test
     */
    function shouldNotThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $em = $this->createEntityManager($evm);
        $em->getClassMetadata(self::CAR);
    }
}

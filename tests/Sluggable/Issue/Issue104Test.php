<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Gedmo\Sluggable\SluggableListener;

class Issue104Test extends ObjectManagerTestCase
{
    const CAR = 'Fixture\Sluggable\Issue104\Car';

    /**
     * @test
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    function shouldThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $em = $this->createEntityManager($evm);
        $em->getClassMetadata(self::CAR);
    }
}

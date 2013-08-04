<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

class Issue104Test extends ObjectManagerTestCase
{
    const CAR = 'Fixture\Sluggable\Issue104\Car';

    /**
     * @test
     */
    public function shouldNotThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $em = $this->createEntityManager($evm);
        $em->getClassMetadata(self::CAR);
    }
}

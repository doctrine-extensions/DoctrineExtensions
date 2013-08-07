<?php

namespace Gedmo\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\TestTool\ObjectManagerTestCase;

class Issue104Test extends ObjectManagerTestCase
{
    const CAR = 'Gedmo\Fixture\Sluggable\Issue104\Car';

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

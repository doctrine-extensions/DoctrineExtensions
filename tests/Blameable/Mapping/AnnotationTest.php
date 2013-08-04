<?php

namespace Blameable\Mapping;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;

require_once __DIR__.'/MappingTestCase.php';

class AnnotationTest extends MappingTestCase
{
    /**
     * @test
     */
    public function shouldMapBlameable()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber($blameable = new BlameableListener());
        $blameable->setUserValue('username');

        $em = $this->createEntityManager($evm);

        $meta = $em->getClassMetadata('Fixture\Blameable\Mapping');
        $exm = $blameable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}
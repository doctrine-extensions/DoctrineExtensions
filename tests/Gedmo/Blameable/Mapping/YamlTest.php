<?php

namespace Gedmo\Blameable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Blameable\BlameableListener;

require_once __DIR__ . '/MappingTestCase.php';

class YamlTest extends MappingTestCase
{
    /**
     * @test
     */
    function shouldSupportYamlMapping()
    {
        $yamlDriver = new YamlDriver(__DIR__);

        $evm = new EventManager;
        $evm->addEventSubscriber($blameable = new BlameableListener);
        $blameable->setUserValue('username');

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);

        $meta = $em->getClassMetadata('Gedmo\Fixture\Blameable\Mapping');
        $exm = $blameable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

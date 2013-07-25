<?php

namespace Blameable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Blameable\BlameableListener;

require_once __DIR__ . '/BlameableMappingTestCase.php';

class YamlTest extends BlameableMappingTestCase
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

        $meta = $em->getClassMetadata('Fixture\Blameable\Mapping');
        $config = $blameable->getConfiguration($em, $meta->name);

        $this->assertBlameableMapping($config);
    }
}

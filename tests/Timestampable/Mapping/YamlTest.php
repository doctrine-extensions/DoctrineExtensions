<?php

namespace Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Timestampable\TimestampableListener;

require_once __DIR__ . '/TimestampableMappingTestCase.php';

class YamlTest extends TimestampableMappingTestCase
{
    /**
     * @test
     */
    function shouldSupportYamlMapping()
    {
        $yamlDriver = new YamlDriver(__DIR__);

        $evm = new EventManager;
        $evm->addEventSubscriber($timestampable = new TimestampableListener);

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);

        $meta = $em->getClassMetadata('Fixture\Timestampable\Mapping');
        $config = $timestampable->getConfiguration($em, $meta->name);

        $this->assertTimestampableMapping($config);
    }
}

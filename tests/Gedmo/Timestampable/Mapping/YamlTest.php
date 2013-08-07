<?php

namespace Gedmo\Timestampable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Timestampable\TimestampableListener;

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
        $evm->addEventSubscriber($timestampable = new TimestampableListener);

        $em = $this->createEntityManager($evm);
        $em->getConfiguration()->setMetadataDriverImpl($yamlDriver);

        $meta = $em->getClassMetadata('Gedmo\Fixture\Timestampable\Mapping');
        $exm = $timestampable->getConfiguration($em, $meta->name);

        $this->assertMapping($exm);
    }
}

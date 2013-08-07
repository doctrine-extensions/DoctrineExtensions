<?php

namespace Gedmo\Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Gedmo\Translatable\TranslatableListener;

require_once __DIR__ . '/MappingTestCase.php';

class YamlTest extends MappingTestCase
{
    private $em;
    private $translatable;

    public function setUp()
    {
        $yamlDriver = new YamlDriver(array(__DIR__));
        $yamlSimplifiedDriver = new SimplifiedYamlDriver(array(
            $this->getRootDir().'/lib/Gedmo/Translatable/Mapping/Resources' => 'Gedmo\Translatable\Entity\MappedSuperclass'
        ), '.orm.yml');
        $chain = new MappingDriverChain;
        $chain->addDriver($yamlSimplifiedDriver, 'Gedmo\Translatable');
        $chain->addDriver($yamlDriver, 'Gedmo\Fixture\Unmapped');

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->setMetadataDriverImpl($chain);
    }

    /**
     * @test
     */
    function shouldSupportYamlMapping()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Fixture\Unmapped\Translatable');
        $this->assertMapping($this->translatable->getConfiguration($this->em, $meta->name));
    }
}

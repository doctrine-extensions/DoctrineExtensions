<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Gedmo\Translatable\TranslatableListener;
use TestTool\ObjectManagerTestCase;

class YamlTest extends ObjectManagerTestCase
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
        $chain->addDriver($yamlDriver, 'Fixture\Unmapped');

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
        $meta = $this->em->getClassMetadata('Fixture\Unmapped\Translatable');
        $config = $this->translatable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('translationClass', $config);
        $this->assertEquals('Fixture\Unmapped\TranslatableTranslation', $config['translationClass']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(3, $config['fields']);
        $this->assertArrayHasKey('title', $config['fields']);
        $this->assertArrayHasKey('content', $config['fields']);
        $this->assertArrayHasKey('author', $config['fields']);
    }
}

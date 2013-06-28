<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseOM;

class YamlTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Translatable\TranslatableListener
     */
    private $translatable;

    private $rootProjectDirectory;

    public function setUp()
    {
        parent::setUp();

        $this->rootProjectDirectory = realpath(__DIR__.'/../../..');
        $yamlDriver = new YamlDriver(array(__DIR__));
        $yamlSimplifiedDriver = new SimplifiedYamlDriver(array(
            $this->rootProjectDirectory.'/lib/Gedmo/Translatable/Mapping/Resources' => 'Gedmo\Translatable\Entity\MappedSuperclass'
        ), '.orm.yml');
        $chain = new DriverChain;
        $chain->addDriver($yamlSimplifiedDriver, 'Gedmo\Translatable');
        $chain->addDriver($yamlDriver, 'Fixture\Unmapped');

        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->translatable = new TranslatableListener);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Fixture\Unmapped\Translatable',
            'Fixture\Unmapped\TranslatableTranslation',
        ), $chain);
    }

    /**
     * @test
     */
    function shouldSupportXmlMapping()
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

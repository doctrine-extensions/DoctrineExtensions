<?php

namespace Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Gedmo\Translatable\TranslatableListener;
use TestTool\ObjectManagerTestCase;
use DOMDocument;

class XmlTest extends ObjectManagerTestCase
{
    private $em;
    private $translatable;

    public function setUp()
    {
        $xmlDriver = new XmlDriver(__DIR__);
        $xmlSimplifiedDriver = new SimplifiedXmlDriver(array(
            $this->getRootDir().'/lib/Gedmo/Translatable/Mapping/Resources' => 'Gedmo\Translatable\Entity\MappedSuperclass'
        ), '.orm.xml');
        $chain = new MappingDriverChain;
        $chain->addDriver($xmlSimplifiedDriver, 'Gedmo\Translatable');
        $chain->addDriver($xmlDriver, 'Fixture\Unmapped');

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->setMetadataDriverImpl($chain);
    }

    /**
     * @TODO: find a way to validate xsd
     * test
     */
    function shouldValidateMappingWithXsdSchema()
    {
        $ormXsd = $this->rootProjectDirectory.'/tests/xsd/orm.xsd';
        $gedmoXsd = $this->rootProjectDirectory.'/schemas/orm/doctrine-extensions-mapping-2-4.xsd';
        $mappingXml = file_get_contents(__DIR__.'/Fixture.Unmapped.Translatable.dcm.xml');
        //$mappingXml = str_replace('http://doctrine-project.org/schemas/orm/doctrine-mapping', 'file://'.$ormXsd, $mappingXml);
        $doc = new DOMDocument();
        $doc->loadXml($mappingXml);
        $this->assertTrue(
            $doc->schemaValidate($gedmoXsd),
            "Translatable was not validated with XSD"
        );
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

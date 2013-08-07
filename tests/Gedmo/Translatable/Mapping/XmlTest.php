<?php

namespace Gedmo\Translatable\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Gedmo\Translatable\TranslatableListener;

require_once __DIR__ . '/MappingTestCase.php';

class XmlTest extends MappingTestCase
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
        $chain->addDriver($xmlDriver, 'Gedmo\Fixture\Unmapped');

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->setMetadataDriverImpl($chain);
    }

    /**
     * @test
     */
    function shouldSupportXmlMapping()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Fixture\Unmapped\Translatable');
        $this->assertMapping($this->translatable->getConfiguration($this->em, $meta->name));
    }
}

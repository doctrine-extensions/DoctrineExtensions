<?php
namespace Gedmo\Tree\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Tool\BaseTestCaseMongoODM;
use Tree\Fixture\Document\CategoryCustomId;

class Issue1167Test extends BaseTestCaseMongoODM
{
    public function testPersistWithCustomId()
    {
        $c = new CategoryCustomId();
        $c->setId('new_id');
        $this->dm->persist($c);
        $this->dm->flush();
        $this->assertEquals('new_id', $c->getId());
    }

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockDocumentManager($evm);
    }
}
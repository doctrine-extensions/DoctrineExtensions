<?php

namespace Gedmo\Tree\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Tool\BaseTestCaseMongoODM;
use Tree\Fixture\Issue1167\Category;

class Issue1167Test extends BaseTestCaseMongoODM
{
    public function testPersistWith()
    {
        $c = new Category();
        $c->setId('new_id');
        $this->dm->persist($c);
        $this->dm->flush();
        $this->assertEquals('new_id', $c->getId());
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockDocumentManager($evm);
    }
}
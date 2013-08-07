<?php

namespace Gedmo\Sortable\Mapping;

use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Sortable\Mapping\SortableMetadata;

class MappingTestCase extends ObjectManagerTestCase
{
    protected function assertMapping(SortableMetadata $exm)
    {
        $this->assertFalse($exm->isEmpty());
        $this->assertCount(2, $fields = $exm->getFields());

        $this->assertContains('position', $fields);
        $this->assertContains('sortedByOccupation', $fields);

        $opts = $exm->getOptions('position');
        $this->assertCount(0, $opts['groups']);
        $this->assertSame('Gedmo\Fixture\Sortable\Mapping', $opts['rootClass']);

        $opts = $exm->getOptions('sortedByOccupation');
        $this->assertSame('Gedmo\Fixture\Sortable\Mapping', $opts['rootClass']);
        $this->assertCount(2, $gr = $opts['groups']);
        $this->assertContains('company', $gr);
        $this->assertContains('occupation', $gr);
    }
}


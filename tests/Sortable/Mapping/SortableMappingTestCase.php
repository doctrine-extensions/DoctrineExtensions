<?php

namespace Sortable\Mapping;

use TestTool\ObjectManagerTestCase;

class SortableMappingTestCase extends ObjectManagerTestCase
{
    protected function assertSortableMapping($config)
    {
        $this->assertTrue(!empty($config));
        $this->assertCount(2, $config);
        $this->assertArrayHasKey('position', $config);
        $this->assertArrayHasKey('sortedByOccupation', $config);

        $positionGroups = $config['position'];
        $this->assertCount(0, $positionGroups);

        $occGroups = $config['sortedByOccupation'];
        $this->assertCount(2, $occGroups);
        $this->assertTrue(in_array('company', $occGroups, true));
        $this->assertTrue(in_array('occupation', $occGroups, true));
    }
}


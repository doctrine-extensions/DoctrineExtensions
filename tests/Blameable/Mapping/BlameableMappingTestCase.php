<?php

namespace Blameable\Mapping;

use TestTool\ObjectManagerTestCase;

class BlameableMappingTestCase extends ObjectManagerTestCase
{
    protected function assertBlameableMapping($config)
    {
        $this->assertTrue(!empty($config));
        $this->assertArrayHasKey('create', $config);
        $this->assertTrue(in_array('createdBy', $config['create'], true));

        $this->assertArrayHasKey('update', $config);
        $this->assertTrue(in_array('updatedBy', $config['update'], true));

        $this->assertArrayHasKey('change', $config);
        $this->assertCount(2, $config['change']);

        $changedAt = $config['change'][0];

        $this->assertSame('changedBy', $changedAt['field']);
        $tracked = $changedAt['trackedField'];
        $this->assertTrue(is_array($tracked));
        $this->assertCount(2, $tracked);
        $this->assertTrue(in_array('title', $tracked, true));
        $this->assertTrue(in_array('body', $tracked, true));
        $this->assertNull($changedAt['value']);

        $publishedAt = $config['change'][1];

        $this->assertSame('publishedBy', $publishedAt['field']);
        $this->assertSame('published', $publishedAt['trackedField']);
        $this->assertSame(true, (bool)$publishedAt['value']);
    }
}


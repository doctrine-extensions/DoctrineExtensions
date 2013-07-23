<?php

namespace Timestampable\Mapping;

use TestTool\ObjectManagerTestCase;

class TimestampableMappingTestCase extends ObjectManagerTestCase
{
    protected function assertTimestampableMapping($config)
    {
        $this->assertTrue(!empty($config));
        $this->assertArrayHasKey('create', $config);
        $this->assertTrue(in_array('createdAt', $config['create'], true));

        $this->assertArrayHasKey('update', $config);
        $this->assertTrue(in_array('updatedAt', $config['update'], true));

        $this->assertArrayHasKey('change', $config);
        $this->assertCount(2, $config['change']);

        $changedAt = $config['change'][0];

        $this->assertSame('changedAt', $changedAt['field']);
        $tracked = $changedAt['trackedField'];
        $this->assertTrue(is_array($tracked));
        $this->assertCount(2, $tracked);
        $this->assertTrue(in_array('title', $tracked, true));
        $this->assertTrue(in_array('body', $tracked, true));
        $this->assertNull($changedAt['value']);

        $publishedAt = $config['change'][1];

        $this->assertSame('publishedAt', $publishedAt['field']);
        $this->assertSame('published', $publishedAt['trackedField']);
        $this->assertSame(true, (bool)$publishedAt['value']);
    }
}


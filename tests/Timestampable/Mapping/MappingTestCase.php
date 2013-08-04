<?php

namespace Timestampable\Mapping;

use TestTool\ObjectManagerTestCase;
use Gedmo\Timestampable\Mapping\TimestampableMetadata;

class MappingTestCase extends ObjectManagerTestCase
{
    protected function assertMapping(TimestampableMetadata $exm)
    {
        $this->assertFalse($exm->isEmpty());
        $this->assertCount(4, $fields = $exm->getFields());

        $this->assertContains('createdAt', $fields);
        $this->assertContains('updatedAt', $fields);
        $this->assertContains('changedAt', $fields);
        $this->assertContains('publishedAt', $fields);

        $opts = $exm->getOptions('createdAt');
        $this->assertSame('create', $opts['on']);

        $opts = $exm->getOptions('updatedAt');
        $this->assertSame('update', $opts['on']);

        $opts = $exm->getOptions('changedAt');
        $this->assertSame('change', $opts['on']);
        $this->assertTrue(is_array($opts['field']));
        $this->assertCount(2, $opts['field']);
        $this->assertTrue(in_array('title', $opts['field'], true));
        $this->assertTrue(in_array('body', $opts['field'], true));
        $this->assertNull($opts['value']);

        $opts = $exm->getOptions('publishedAt');
        $this->assertSame('change', $opts['on']);
        $this->assertSame('published', $opts['field']);
        $this->assertSame(true, (bool)$opts['value']);
    }
}


<?php

namespace Blameable\Mapping;

use TestTool\ObjectManagerTestCase;
use Gedmo\Blameable\Mapping\BlameableMetadata;

class MappingTestCase extends ObjectManagerTestCase
{
    protected function assertMapping(BlameableMetadata $exm)
    {
        $this->assertFalse($exm->isEmpty());
        $this->assertCount(4, $fields = $exm->getFields());

        $this->assertContains('createdBy', $fields);
        $this->assertContains('updatedBy', $fields);
        $this->assertContains('changedBy', $fields);
        $this->assertContains('publishedBy', $fields);

        $opts = $exm->getOptions('createdBy');
        $this->assertSame('create', $opts['on']);

        $opts = $exm->getOptions('updatedBy');
        $this->assertSame('update', $opts['on']);

        $opts = $exm->getOptions('changedBy');
        $this->assertSame('change', $opts['on']);
        $this->assertTrue(is_array($opts['field']));
        $this->assertCount(2, $opts['field']);
        $this->assertTrue(in_array('title', $opts['field'], true));
        $this->assertTrue(in_array('body', $opts['field'], true));
        $this->assertNull($opts['value']);

        $opts = $exm->getOptions('publishedBy');
        $this->assertSame('change', $opts['on']);
        $this->assertSame('published', $opts['field']);
        $this->assertSame(true, (bool)$opts['value']);
    }
}


<?php

namespace Gedmo\Tests\Sluggable\Fixture\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class FakeFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        throw new \BadMethodCallException('Do nothing, it\'s a fake !');
    }
}

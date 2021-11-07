<?php

namespace Gedmo\Tests\Mapping\Fixture\Unmapped;

use Gedmo\Mapping\Annotation\Timestampable as Tmsp;

class Timestampable
{
    private $id;

    /**
     * @Tmsp(on="create")
     */
    private $created;
}

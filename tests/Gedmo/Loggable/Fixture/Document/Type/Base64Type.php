<?php

namespace Loggable\Fixture\Document\Type;

use Doctrine\ODM\MongoDB\Types\Type;

class Base64Type extends Type
{
    public function convertToDatabaseValue($value)
    {
        return base64_encode($value);
    }

    public function convertToPHPValue($value)
    {
        return base64_decode($value);
    }

    public function closureToPHP()
    {
        return '$return = base64_decode($value);';
    }
}

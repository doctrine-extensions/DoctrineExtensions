<?php

namespace Loggable\Fixture\Entity\Type;



use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class Base64Type extends StringType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return base64_encode($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return base64_decode($value);
    }

    public function getName()
    {
        return 'base64';
    }


}

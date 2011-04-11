<?php

namespace Translatable\Fixture\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Custom extends Type
{
    const NAME = 'custom';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return serialize($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        $val = unserialize($value);
        if ($val === false && $value !== 'b:0;') {
            new \Exception('Conversion failed');
        }
        return $val;
    }

    public function getName()
    {
        return self::NAME;
    }

}

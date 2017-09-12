<?php

namespace Gedmo\Sortable;

use Doctrine\DBAL\Types\Type;

class CustomType extends Type
{
    const MYTYPE = 'mytype'; // modify to match your type name

    public function getSQLDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getBinaryTypeDeclarationSQL(
            array(
                'length' => '16',
                'fixed'  => false,
            )
        );
    }


    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }
        $unpacked =  unpack('H*', $value);
        return array_pop($unpacked);
    }

    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }
        return hex2bin($value);
    }

    public function getName()
    {
        return self::MYTYPE; // modify to match your constant name
    }
}

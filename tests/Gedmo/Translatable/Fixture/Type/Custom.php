<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

if (class_exists(ArrayType::class)) {
    // DBAL 3.x
    /**
     * Helper class to address compatibility issues between DBAL 3.x and 4.x.
     *
     * @internal
     */
    abstract class CompatType extends Type
    {
        /**
         * @param array<string, mixed> $column
         */
        public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
        {
            return $this->doGetSQLDeclaration($column, $platform);
        }

        /**
         * @param mixed $value
         */
        public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
        {
            return $this->doConvertToDatabaseValue($value, $platform);
        }

        /**
         * @param mixed $value
         */
        public function convertToPHPValue($value, AbstractPlatform $platform): mixed
        {
            return $this->doConvertToPHPValue($value, $platform);
        }

        /**
         * @param array<string, mixed> $column
         */
        abstract protected function doGetSQLDeclaration(array $column, AbstractPlatform $platform): string;

        abstract protected function doConvertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed;

        abstract protected function doConvertToPHPValue(mixed $value, AbstractPlatform $platform): mixed;
    }
} else {
    // DBAL 4.x
    /**
     * Helper class to address compatibility issues between DBAL 3.x and 4.x.
     *
     * @internal
     */
    abstract class CompatType extends Type
    {
        /**
         * @param array<string, mixed> $column
         */
        public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
        {
            return $this->doGetSQLDeclaration($column, $platform);
        }

        public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
        {
            return $this->doConvertToDatabaseValue($value, $platform);
        }

        public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
        {
            return $this->doConvertToPHPValue($value, $platform);
        }

        /**
         * @param array<string, mixed> $column
         */
        abstract protected function doGetSQLDeclaration(array $column, AbstractPlatform $platform): string;

        abstract protected function doConvertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed;

        abstract protected function doConvertToPHPValue(mixed $value, AbstractPlatform $platform): mixed;
    }
}

class Custom extends CompatType
{
    private const NAME = 'custom';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param array<string, mixed> $column
     */
    protected function doGetSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    protected function doConvertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        return serialize($value);
    }

    protected function doConvertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        $val = unserialize($value);
        if (false === $val && 'b:0;' !== $value) {
            if (!class_exists(ValueNotConvertible::class)) {
                throw ConversionException::conversionFailed($value, $this->getName());
            }

            throw ValueNotConvertible::new($value, $this->getName());
        }

        return $val;
    }
}

<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable\Mapping\Event\Adapter;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ORM extends BaseAdapterORM implements TimestampableAdapter
{
    /**
     * @param ClassMetadata $meta
     */
    public function getDateValue($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        $converter = Type::getType($mapping['type'] ?? Types::DATETIME_MUTABLE);
        $platform = $this->getObjectManager()->getConnection()->getDriver()->getDatabasePlatform();

        return $converter->convertToPHPValue($this->getRawDateValue($mapping), $platform);
    }

    /**
     * Generates current timestamp for the specified mapping
     *
     * @param array<string, mixed> $mapping
     *
     * @return \DateTimeInterface|int
     */
    private function getRawDateValue(array $mapping)
    {
        $datetime = new \DateTime();
        $type = $mapping['type'] ?? null;

        if ('integer' === $type) {
            return (int) $datetime->format('U');
        }

        if (in_array($type, ['date_immutable', 'time_immutable', 'datetime_immutable', 'datetimetz_immutable'], true)) {
            return \DateTimeImmutable::createFromMutable($datetime);
        }

        return $datetime;
    }
}

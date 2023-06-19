<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Event\Adapter;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\SoftDeleteable\Mapping\Event\SoftDeleteableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for SoftDeleteable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
final class ORM extends BaseAdapterORM implements SoftDeleteableAdapter
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

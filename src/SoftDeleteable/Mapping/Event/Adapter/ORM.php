<?php

namespace Gedmo\SoftDeleteable\Mapping\Event\Adapter;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Gedmo\SoftDeleteable\Mapping\Event\SoftDeleteableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for SoftDeleteable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements SoftDeleteableAdapter
{
    /**
     * {@inheritDoc}
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
        if (isset($mapping['type']) && 'integer' === $mapping['type']) {
            return time();
        }
        if (isset($mapping['type']) && in_array($mapping['type'], ['date_immutable', 'time_immutable', 'datetime_immutable', 'datetimetz_immutable'], true)) {
            return new \DateTimeImmutable();
        }
        return \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))
            ->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
    }
    
}

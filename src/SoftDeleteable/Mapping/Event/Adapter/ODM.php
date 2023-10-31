<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Event\Adapter;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\Adapter\ODM as BaseAdapterODM;
use Gedmo\SoftDeleteable\Event\ODM\PostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\ODM\PreSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Mapping\Event\SoftDeleteableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for SoftDeleteable behavior.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
final class ODM extends BaseAdapterODM implements SoftDeleteableAdapter
{
    /**
     * @param ClassMetadata $meta
     */
    public function getDateValue($meta, $field)
    {
        $datetime = new \DateTime();
        $mapping = $meta->getFieldMapping($field);
        $type = $mapping['type'] ?? null;

        if ('timestamp' === $type) {
            return (int) $datetime->format('U');
        }

        if (in_array($type, ['date_immutable', 'time_immutable', 'datetime_immutable', 'datetimetz_immutable'], true)) {
            return \DateTimeImmutable::createFromMutable($datetime);
        }

        return $datetime;
    }

    /**
     * @param DocumentManager $manager
     */
    public function createPreSoftDeleteEventArgs(object $object, ObjectManager $manager): PreSoftDeleteEventArgs
    {
        return new PreSoftDeleteEventArgs($object, $manager);
    }

    /**
     * @param DocumentManager $manager
     */
    public function createPostSoftDeleteEventArgs(object $object, ObjectManager $manager): PostSoftDeleteEventArgs
    {
        return new PostSoftDeleteEventArgs($object, $manager);
    }
}

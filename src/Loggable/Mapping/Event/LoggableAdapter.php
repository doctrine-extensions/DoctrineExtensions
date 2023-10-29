<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Event;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the Loggable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface LoggableAdapter extends AdapterInterface
{
    /**
     * Get the default object class name used to store the log entries.
     *
     * @return string
     *
     * @phpstan-return class-string
     */
    public function getDefaultLogEntryClass();

    /**
     * Checks whether an identifier should be generated post insert.
     *
     * @param ClassMetadata $meta
     *
     * @return bool
     */
    public function isPostInsertGenerator($meta);

    /**
     * Get the new version number for an object.
     *
     * @param ClassMetadata $meta
     * @param object        $object
     *
     * @return int
     */
    public function getNewVersion($meta, $object);
}

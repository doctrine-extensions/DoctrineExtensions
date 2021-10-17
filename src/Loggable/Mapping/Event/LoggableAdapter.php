<?php

namespace Gedmo\Loggable\Mapping\Event;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the Loggable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface LoggableAdapter extends AdapterInterface
{
    /**
     * Get the default object class name used to store the log entries.
     *
     * @return string
     * @phpstan-return class-string
     */
    public function getDefaultLogEntryClass();

    /**
     * Checks whether an identifier should be generated post insert.
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

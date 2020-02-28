<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Loggable annotation for Loggable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Loggable extends Annotation
{
    /** @var string */
    public $logEntryClass;
}

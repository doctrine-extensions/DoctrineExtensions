<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Loggable annotation for Loggable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Loggable
{
    /** @var string */
    public $logEntryClass;

    /**
     *
     * @param null|string $logEntryClass
     *
     * @return void
     */
    public function __construct($logEntryClass = null)
    {
        $this->logEntryClass = $logEntryClass;
    }
}
